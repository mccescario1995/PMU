import os
import sys

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))

try:
    from dotenv import load_dotenv
    env_path = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".env"))
    load_dotenv(env_path)
except Exception:
    pass

import pandas as pd
from flask import Flask, request, jsonify
from flask_cors import CORS
from sqlalchemy import create_engine
from sqlalchemy.pool import QueuePool

from src.pmu_client import PMUClient
from src.config import Config
from src.logger import logger
from src.forecaster import Forecaster, ForecastResult

config = Config()
engine = create_engine(
    config.db_url,
    poolclass=QueuePool,
    pool_size=5,
    max_overflow=10,
    pool_timeout=30,
    pool_recycle=3600,
)

app = Flask(__name__)
CORS(app)

pmu_client = PMUClient(
    base_url=config.pmu_api_url,
    token=config.pmu_api_token,
) if config.pmu_api_token else None

forecaster = Forecaster(config=config, client=pmu_client)

if os.path.exists("outputs/models/arima.joblib"):
    try:
        forecaster.load_all()
        logger.info("Loaded cached models on startup")
    except Exception as e:
        logger.warning(f"Failed to load cached models: {e}")


def get_historical_features() -> pd.DataFrame:
        df = pd.read_sql(
            """
            SELECT tr.report_date, tr.year_num, tr.month_num, tr.day_num,
                   tr.day_of_week, tr.quarter_num, tr.is_weekend,
                   tr.is_month_start, tr.is_month_end,
                   tr.revenue_target, tr.log_revenue,
                   tr.temp_celsius, tr.precipitation_mm, tr.wind_speed,
                   trf.revenue_lag_1d, trf.revenue_lag_7d, trf.revenue_lag_365d,
                   trf.revenue_rolling_7d_mean, trf.revenue_rolling_30d_mean
            FROM transaction_revenue tr
            LEFT JOIN transaction_revenue_features trf ON tr.report_date = trf.report_date
            ORDER BY tr.report_date ASC
        """,
            con=engine,
        )
        if "report_date" in df.columns and not df.empty:
            df["report_date"] = df["report_date"].astype(str)
        return df


@app.route("/", methods=["GET"])
@app.route("/health", methods=["GET"])
def health_check():
    return jsonify({
        "status": "online",
        "service": "PMU Revenue ML Forecast API",
        "version": "1.0.0",
    })


@app.route("/forecast", methods=["POST"])
def forecast():
    data = request.get_json(force=True) or {}
    model_name = data.get("model", "arima")
    days = int(data.get("days", config.forecast_days))
    post_to_api = data.get("post_to_api", True)
    try:
        result = forecaster.train_model(
            model_name, days=days, post_to_api=post_to_api,
        )
        if result is None or result.error:
            return jsonify({"error": result.error if result else "Unknown model"}), 500
        forecasts = [
            {"date": fc["date"], "predicted_revenue": fc.get("predicted_revenue", 0.0)}
            for fc in result.forecasts
        ]
        return jsonify({
            "forecasts": forecasts,
            "metrics": result.metrics,
        })
    except Exception as e:
        logger.error(f"forecast error: {e}", exc_info=True)
        return jsonify({"error": str(e)}), 500


@app.route("/train", methods=["POST"])
def train():
    data = request.get_json(force=True) or {}
    models = data.get("models", ["arima", "sarima", "linear_regression"])
    days = int(data.get("days", config.forecast_days))
    post_to_api = data.get("post_to_api", True)
    try:
        results = forecaster.train_all(
            days=days, post_to_api=post_to_api,
        )
        output = {}
        for name, result in results.items():
            output[name] = {
                "forecasts": [
                    {"date": fc["date"], "predicted_revenue": fc.get("predicted_revenue", 0.0)}
                    for fc in result.forecasts
                ],
                "metrics": result.metrics,
                "error": result.error,
            }
        return jsonify({"status": "success", "results": output})
    except Exception as e:
        logger.error(f"train error: {e}", exc_info=True)
        return jsonify({"error": str(e)}), 500


@app.route("/weather/backfill", methods=["POST"])
def weather_backfill():
    try:
        df = get_historical_features()
        if df.empty:
            return jsonify({"error": "No records in transaction_revenue"}), 404
        dates = df["report_date"].tolist()
        result = forecaster.weather_manager.backfill(dates)
        return jsonify({
            "status": "success",
            "dates_backfilled": len(result),
            "total_dates": len(dates),
        })
    except Exception as e:
        logger.error(f"weather backfill error: {e}", exc_info=True)
        return jsonify({"error": str(e)}), 500


@app.route("/weather/sync", methods=["POST"])
def weather_sync():
    data = request.get_json(force=True) or {}
    date_str = data.get("date")
    if not date_str:
        return jsonify({"error": "date required"}), 400
    try:
        from datetime import date as date_cls
        d = date_cls.fromisoformat(date_str)
        success = forecaster.weather_manager.sync_weather_for_date(d)
        if success:
            return jsonify({"status": "success", "date": date_str})
        return jsonify({"status": "failed", "date": date_str}), 500
    except Exception as e:
        logger.error(f"weather sync error: {e}", exc_info=True)
        return jsonify({"error": str(e)}), 500


@app.route("/weather/status", methods=["GET"])
def weather_status():
    try:
        status = forecaster.weather_manager.get_backfill_status()
        cached = forecaster.weather_manager.get_cached_weather()
        return jsonify({
            "status": "success",
            "backfill": status,
            "cached_dates": list(cached.keys())[:50],
            "cached_count": len(cached),
        })
    except Exception as e:
        logger.error(f"weather status error: {e}", exc_info=True)
        return jsonify({"error": str(e)}), 500


@app.route("/predict/all", methods=["GET", "POST"])
def predict_all():
    days = int(request.args.get("days", config.forecast_days))
    try:
        df = get_historical_features()
        if df.empty:
            return jsonify({"error": "No records found in transaction_revenue"}), 404

        results = forecaster.forecast(
            models=["arima", "sarima", "linear_regression"],
            days=days,
            use_weather=True,
            concurrent=True,
        )

        predictions = []
        n = min(days, _max_forecast_length(results))
        for i in range(n):
            entry = {"date": _get_date(results, i)}
            for model_name, result in results.items():
                if result.error:
                    entry[f"{model_name}_error"] = result.error
                elif i < len(result.forecasts):
                    fc = result.forecasts[i]
                    entry[f"{model_name}_predicted_revenue"] = fc.get("predicted_revenue", 0.0)
                else:
                    entry[f"{model_name}_predicted_revenue"] = 0.0
            predictions.append(entry)

        return jsonify({
            "status": "success",
            "forecast_horizon_days": days,
            "models": list(results.keys()),
            "predictions": predictions,
        })
    except Exception as e:
        logger.error(f"predict_all error: {e}", exc_info=True)
        return jsonify({"error": str(e)}), 500


@app.route("/predict/<model_name>", methods=["GET", "POST"])
def predict_single(model_name):
    days = int(request.args.get("days", config.forecast_days))
    try:
        df = get_historical_features()
        if df.empty:
            return jsonify({"error": "No records found in transaction_revenue"}), 404

        results = forecaster.forecast(
            models=[model_name],
            days=days,
            use_weather=True,
        )

        if model_name not in results:
            return jsonify({"error": f"Unknown model: {model_name}"}), 400

        result = results[model_name]
        if result.error:
            return jsonify({"error": result.error}), 500

        predictions = []
        for i, fc in enumerate(result.forecasts[:days]):
            entry = dict(fc)
            predictions.append(entry)

        return jsonify({
            "status": "success",
            "model": model_name,
            "forecast_horizon_days": len(predictions),
            "metrics": result.metrics,
            "predictions": predictions,
        })
    except Exception as e:
        logger.error(f"predict_single error: {e}", exc_info=True)
        return jsonify({"error": str(e)}), 500

@app.route("/debug/mysql-connection", methods=["GET"])
def debug_mysql_connection():
    try:
        ip = socket.gethostbyname("srv1041.hstgr.io")

        sock = socket.create_connection(
            ("srv1041.hstgr.io", 3306),
            timeout=10
        )
        sock.close()

        return {
            "success": True,
            "dns": ip,
            "mysql_port": 3306,
            "message": "Render can reach Hostinger MySQL"
        }

    except Exception as e:
        return {
            "success": False,
            "error_type": type(e).__name__,
            "message": str(e)
        }


def _max_forecast_length(results: dict) -> int:
    max_len = 0
    for result in results.values():
        if not result.error:
            max_len = max(max_len, len(result.forecasts))
    return max_len


def _get_date(results: dict, index: int) -> str:
    for result in results.values():
        if not result.error and index < len(result.forecasts):
            return result.forecasts[index].get("date", "")
    return ""


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=config.port)
