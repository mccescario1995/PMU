from typing import Optional
import os
import yaml
import pandas as pd
import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
from pmu_client import PMUClient
from data_loader import load_transactions, load_weather, merge_data
from models.linear_regression import LinearRegressionModel
from models.amira import AMIRAModel
from models.samira import SAMIRAModel

OUTPUTS_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "outputs"))


def load_config(path: str = "configs/models.yaml") -> dict:
    with open(path, "r") as f:
        return yaml.safe_load(f)


def _save_outputs(model_name: str, forecasts: list[dict], metrics: dict, historical_df: pd.DataFrame):
    pred_dir = os.path.join(OUTPUTS_DIR, "predictions")
    plot_dir = os.path.join(OUTPUTS_DIR, "plots")
    os.makedirs(pred_dir, exist_ok=True)
    os.makedirs(plot_dir, exist_ok=True)

    pred_df = pd.DataFrame(forecasts)
    csv_path = os.path.join(pred_dir, f"{model_name}_forecast.csv")
    pred_df.to_csv(csv_path, index=False)

    plt.figure(figsize=(10, 5))
    if not historical_df.empty:
        hist = historical_df.copy()
        hist["date"] = pd.to_datetime(hist["date"])
        plt.plot(hist["date"], hist["revenue"], label="Historical", color="gray")
    pred_df["date"] = pd.to_datetime(pred_df["date"])
    plt.plot(pred_df["date"], pred_df["predicted_revenue"], label="Forecast", color="red")
    plt.title(f"{model_name.title()} Forecast")
    plt.xlabel("Date")
    plt.ylabel("Revenue")
    plt.legend()
    plt.tight_layout()
    plot_path = os.path.join(plot_dir, f"{model_name}_forecast.png")
    plt.savefig(plot_path)
    plt.close()

    return csv_path, plot_path


def run_linear(client: PMUClient, config: Optional[dict] = None):
    cfg = config or load_config()["linear_regression"]
    model = LinearRegressionModel(alpha=cfg.get("alpha", 1.0), fit_intercept=cfg.get("fit_intercept", True))
    return _train_and_forecast(client, model, "linear_regression")


def run_amira(client: PMUClient, config: Optional[dict] = None):
    cfg = config or load_config()["amira"]
    p, d, q = cfg.get("p", 1), cfg.get("d", 1), cfg.get("q", 1)
    model = AMIRAModel(order=(p, d, q))
    return _train_and_forecast(client, model, "amira")


def run_samira(client: PMUClient, config: Optional[dict] = None):
    cfg = config or load_config()["samira"]
    p, d, q = cfg.get("p", 1), cfg.get("d", 1), cfg.get("q", 1)
    m = cfg.get("m", 12)
    model = SAMIRAModel(order=(p, d, q), seasonal_order=(1, d, 1, m))
    return _train_and_forecast(client, model, "samira", exog_col="temperature" if cfg.get("exog") else None)


def _train_and_forecast(client: PMUClient, model, model_name: str, exog_col: Optional[str] = None):
    from datetime import date, timedelta
    import pandas as pd

    end = date.today()
    start = end - timedelta(days=365 * 2)

    transactions = load_transactions(client, start, end)
    weather = load_weather(client, start, end)
    df = merge_data(transactions, weather)

    if df.empty:
        raise ValueError("No data fetched from PMUAPI")

    model.fit(df, exog_col=exog_col)
    metrics = model.evaluate()

    forecast = model.predict(df, steps=30, exog_col=exog_col)
    if hasattr(forecast, "index"):
        forecast_dates = [d.date().isoformat() for d in forecast.index]
        values = forecast.values.tolist()
    else:
        forecast_dates = [(end + timedelta(days=i + 1)).isoformat() for i in range(len(forecast))]
        values = forecast.tolist() if hasattr(forecast, "tolist") else list(forecast)

    forecasts = [{"date": d, "predicted_revenue": float(v)} for d, v in zip(forecast_dates, values)]

    csv_path, plot_path = _save_outputs(model_name, forecasts, metrics, df)
    return {
        "model": model_name,
        "metrics": metrics,
        "forecasts": forecasts,
        "csv_path": csv_path,
        "plot_path": plot_path,
    }
