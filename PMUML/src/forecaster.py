import logging
import os
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import date, timedelta
from typing import Any, Dict, List, Optional

import pandas as pd
import yaml

from src.base_model import BaseModel
from src.config import Config
from src.features import prepare_dataset, generate_future_features
from src.logger import logger
from src.weather_service import WeatherManager

from src.models.arima import ARIMAModel
from src.models.sarima import SARIMAModel
from src.models.linear_regression import LinearRegressionModel


class ForecastResult:
    def __init__(self, model_name: str, forecasts: list, metrics: dict = None, error: str = None):
        self.model_name = model_name
        self.forecasts = forecasts
        self.metrics = metrics or {}
        self.error = error

    def to_dict(self) -> dict:
        return {
            "model": self.model_name,
            "forecasts": self.forecasts,
            "metrics": self.metrics,
            "error": self.error,
        }


class Forecaster:
    def __init__(self, config: Optional[Config] = None, client: Optional[Any] = None):
        self.config = config or Config()
        self.client = client
        self._models: Dict[str, Any] = {}
        self._weather_manager: Optional[WeatherManager] = None

    @property
    def weather_manager(self) -> WeatherManager:
        if self._weather_manager is None:
            self._weather_manager = WeatherManager(client=self.client)
            self._weather_manager.load_historical(days=730)
        return self._weather_manager

    def get_model(self, model_name: str):
        if model_name not in self._models:
            if model_name == "arima":
                cfg = self.config.get_model_config("arima")
                self._models[model_name] = ARIMAModel(
                    order=(cfg.get("p", 1), cfg.get("d", 1), cfg.get("q", 1))
                )
            elif model_name == "sarima":
                cfg = self.config.get_model_config("sarima")
                p, d, q = cfg.get("p", 1), cfg.get("d", 1), cfg.get("q", 1)
                m = cfg.get("m", 7)
                self._models[model_name] = SARIMAModel(
                    order=(p, d, q),
                    seasonal_order=(1, d, 1, m),
                )
            elif model_name == "linear_regression":
                cfg = self.config.get_model_config("linear_regression")
                self._models[model_name] = LinearRegressionModel(
                    alpha=cfg.get("alpha", 1.0),
                    fit_intercept=cfg.get("fit_intercept", True),
                    use_log_target=True,
                )
            else:
                raise ValueError(f"Unknown model: {model_name}")
        return self._models[model_name]

    def get_historical_df(self, client=None, days: int = 730) -> pd.DataFrame:
        client = client or self.client
        if client is not None:
            return self._load_from_api(client, days)
        return self._load_from_db()

    def _load_from_db(self, days: Optional[int] = None) -> pd.DataFrame:
        from sqlalchemy import create_engine
        engine = create_engine(self.config.db_url)
        limit = f" LIMIT {days}" if days else ""
        query = f"""
            SELECT tr.report_date, tr.year_num, tr.month_num, tr.day_num,
                   tr.day_of_week, tr.quarter_num, tr.is_weekend,
                   tr.is_month_start, tr.is_month_end,
                   tr.revenue_target, tr.log_revenue,
                   tr.temp_celsius, tr.precipitation_mm, tr.wind_speed,
                   trf.revenue_lag_1d, trf.revenue_lag_7d, trf.revenue_lag_365d,
                   trf.revenue_rolling_7d_mean, trf.revenue_rolling_30d_mean
            FROM transaction_revenue tr
            LEFT JOIN transaction_revenue_features trf ON tr.report_date = trf.report_date
            ORDER BY tr.report_date ASC{limit}
        """
        df = pd.read_sql(query, con=engine)
        if "report_date" in df.columns:
            df["report_date"] = df["report_date"].astype(str)
        return df

    def _load_from_api(self, client, days: int = 730) -> pd.DataFrame:
        from datetime import date as date_cls
        end = date_cls.today()
        start = end - timedelta(days=days)
        from src.data_loader import load_transactions, load_weather, merge_data
        transactions = load_transactions(client, start, end)
        weather = load_weather(client, start, end)
        df = merge_data(transactions, weather)
        if df.empty:
            raise ValueError("No data fetched from PMUAPI")
        return df

    def forecast(
        self,
        models: Optional[List[str]] = None,
        days: int = 30,
        client=None,
        use_weather: bool = True,
        concurrent: bool = False,
        ) -> Dict[str, ForecastResult]:
        client = client or self.client
        # Load historical data first
        df = self.get_historical_df(client=client)
        
        # If no historical data is available, skip training
        if df.empty:
            logger.warning("No historical data available. Skipping model training.")
            return {}

        if models is None:
            models = ["arima", "sarima", "linear_regression"]

        wm = self.weather_manager if use_weather else None
        weather_df = None
        if wm is not None:
            from datetime import date as date_cls, timedelta
            last_date = pd.to_datetime(df["report_date"]).max().date() if not df.empty else date_cls.today()
            weather_df = wm.get_forecast_weather(last_date, days)

        results: Dict[str, ForecastResult] = {}

        if concurrent:
            with ThreadPoolExecutor(max_workers=len(models)) as executor:
                futures = {
                    executor.submit(
                        self._forecast_single, model_name, df, days, wm, weather_df, client
                    ): model_name
                    for model_name in models
                }
                for future in as_completed(futures):
                    model_name = futures[future]
                    results[model_name] = future.result()
        else:
            for model_name in models:
                results[model_name] = self._forecast_single(
                    model_name, df, days, wm, weather_df, client
                )

        return results

    def _forecast_single(
        self,
        model_name: str,
        df: pd.DataFrame,
        days: int,
        wm: Optional[WeatherManager],
        weather_df: Optional[pd.DataFrame],
        client=None,
    ) -> ForecastResult:
        try:
            model = self.get_model(model_name)

            if model_name == "linear_regression":
                model.fit(df)
                future_df = generate_future_features(
                    df.iloc[-1], steps=days, weather_df=weather_df
                )
                forecasts = model.predict(df, steps=days, future_features=future_df)
            elif model_name == "sarima":
                cfg = self.config.get_model_config("sarima")
                exog_col = None
                if cfg.get("exog"):
                    if wm is not None and "temp_celsius" in df.columns:
                        df_with_temp = df.copy()
                        df_with_temp["temp_celsius"] = df_with_temp["temp_celsius"].fillna(0)
                        exog_col = "temp_celsius"
                    elif wm is not None:
                        df_with_temp = df.copy()
                        df_with_temp["temp_celsius"] = df_with_temp.apply(
                            lambda row: wm.get_weather_for_date(
                                pd.to_datetime(row["report_date"]).date()
                            ).get("temp_celsius", 25.0),
                            axis=1,
                        )
                        exog_col = "temp_celsius"
                    else:
                        exog_col = None

                if exog_col:
                    model.fit(df_with_temp, exog_col=exog_col)
                else:
                    model.fit(df)

                if exog_col and weather_df is not None:
                    future_exog = weather_df[["temp_celsius"]].copy()
                else:
                    future_exog = None
                forecasts = model.predict(steps=days, future_exog=future_exog)
            else:
                model.fit(df, target="revenue_target")
                forecasts = model.predict(steps=days)

            metrics = model.evaluate()
            return ForecastResult(model_name=model_name, forecasts=forecasts, metrics=metrics)

        except Exception as e:
            logger.error(f"Forecast failed for {model_name}: {e}", exc_info=True)
            return ForecastResult(model_name=model_name, forecasts=[], error=str(e))

    def save_all(self, directory: str = "outputs/models"):
        os.makedirs(directory, exist_ok=True)
        for model_name, model in self._models.items():
            path = os.path.join(directory, f"{model_name}.joblib")
            model.save(path)
            logger.info(f"Saved {model_name} to {path}")

    def load_all(self, directory: str = "outputs/models"):
        loaded = False
        for model_name in self._models:
            path = os.path.join(directory, f"{model_name}.joblib")
            if os.path.exists(path):
                self._models[model_name] = BaseModel.load(path)
                logger.info(f"Loaded {model_name} from {path}")
                loaded = True
        return loaded

    def train_all(self, days: Optional[int] = None, client=None, use_weather: bool = True,
                  post_to_api: bool = True) -> Dict[str, ForecastResult]:
        days = days or self.config.forecast_days
        client = client or self.client
        results = self.forecast(models=None, days=days, client=client,
                                use_weather=use_weather, concurrent=True)
        if post_to_api and client is not None:
            self._post_results_to_api(results, client)
        return results

    def train_model(self, model_name: str, days: Optional[int] = None, client=None,
                    use_weather: bool = True, post_to_api: bool = True) -> ForecastResult:
        days = days or self.config.forecast_days
        client = client or self.client
        results = self.forecast(models=[model_name], days=days, client=client,
                                use_weather=use_weather, concurrent=False)
        result = results.get(model_name)
        if post_to_api and result and not result.error and client is not None:
            model_version = os.getenv("MODEL_VERSION", f"{model_name}-v1")
            client.post_forecast_batch(result.forecasts, model_version=model_version)
        return result

    def _post_results_to_api(self, results: Dict[str, ForecastResult], client):
        for model_name, result in results.items():
            if result.error or not result.forecasts:
                logger.warning(f"Skipping API post for {model_name}: {result.error or 'no forecasts'}")
                continue
            try:
                model_version = os.getenv("MODEL_VERSION", f"{model_name}-v1")
                posted = client.post_forecast_batch(result.forecasts, model_version=model_version)
                logger.info(f"Posted {len(posted)} forecasts for {model_name}")
            except Exception as e:
                logger.error(f"Failed to post {model_name} forecasts: {e}")
