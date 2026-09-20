from typing import Any, Dict, List, Optional
import logging

import numpy as np
import pandas as pd

from src.logger import logger
try:
    from src.base_model import BaseModel
except ImportError:
    from base_model import BaseModel


class SARIMAModel(BaseModel):
    def __init__(
        self,
        order: tuple = (1, 1, 1),
        seasonal_order: tuple = (1, 1, 1, 7),
        max_training_rows: Optional[int] = None,
        fit_options: Optional[Dict[str, Any]] = None,
        **kwargs,
    ):
        super().__init__(model_name="sarima")
        self.order = order
        self.seasonal_order = seasonal_order
        self.max_training_rows = max_training_rows
        self.fit_options = {"method": "lbfgs", "maxiter": 50}
        if fit_options:
            self.fit_options.update(fit_options)
        self._SARIMAX = None

    def fit(
        self,
        df: pd.DataFrame,
        target: str = "revenue_target",
        date_col: str = "report_date",
        exog_col: Optional[str] = None,
        test_size: float = 0.2,
        **kwargs,
    ):
        from statsmodels.tsa.statespace.sarimax import SARIMAX as _SARIMAX
        self._SARIMAX = _SARIMAX

        data = df.copy()
        data[date_col] = pd.to_datetime(data[date_col])
        series = data.set_index(date_col)[target].asfreq("D")
        series = series.interpolate(method="linear").ffill().fillna(0)

        self.last_date = series.index[-1]

        exog = None
        if exog_col and exog_col in data.columns:
            exog = data.set_index(date_col)[exog_col].asfreq("D").interpolate(method="linear").ffill().fillna(0)

        split = int(len(series) * (1 - test_size))
        train, test = series.iloc[:split], series.iloc[split:]
        self.y_test = test

        train_exog = exog.iloc[:split] if exog is not None else None
        if self.max_training_rows is not None and self.max_training_rows > 0 and len(train) > self.max_training_rows:
            train = train.iloc[-self.max_training_rows:]
            if train_exog is not None:
                train_exog = train_exog.iloc[-self.max_training_rows:]

        fit_kwargs = {"disp": False}
        fit_kwargs.update(self.fit_options)

        try:
            self.result = self._SARIMAX(
                train,
                exog=train_exog,
                order=self.order,
                seasonal_order=self.seasonal_order,
            ).fit(**fit_kwargs)
        except Exception as e:
            logger.warning(f"SARIMAX fit failed, falling back: {e}")
            self.result = self._SARIMAX(train, order=(1, 1, 0)).fit(**fit_kwargs)

        self._is_fitted = True
        return self

    def predict(self, steps: int = 30, future_exog: Optional[pd.DataFrame] = None, return_format: str = "dict", **kwargs) -> List[Dict[str, Any]]:
        if not self._is_fitted or self.result is None:
            raise ValueError("Model has not been fitted yet. Call fit() first.")

        if future_exog is None and self.result.model.exog is not None:
            future_exog = pd.Series(0.0, index=pd.date_range(
                start=self.last_date + pd.Timedelta(days=1),
                periods=steps,
                freq="D",
            ))

        forecast_res = self.result.get_forecast(steps=steps, exog=future_exog)
        forecast_values = forecast_res.predicted_mean

        future_dates = pd.date_range(
            start=self.last_date + pd.Timedelta(days=1),
            periods=steps,
            freq="D",
        )

        forecast_df = pd.DataFrame({
            "date": future_dates.strftime("%Y-%m-%d"),
            "predicted_revenue": np.maximum(0, forecast_values.values).round(2),
        })

        if return_format == "dict":
            return forecast_df.to_dict(orient="records")
        return forecast_df

    def evaluate(self) -> Dict[str, float]:
        if self.y_test is None or self.result is None or not self._is_fitted:
            return {}
        exog = None
        if self.result.model.exog is not None:
            exog = pd.Series(0.0, index=self.y_test.index)
        preds = self.result.get_forecast(steps=len(self.y_test), exog=exog).predicted_mean
        rmse = float(np.sqrt(np.mean((self.y_test.values - preds.values) ** 2)))
        mae = float(np.mean(np.abs(self.y_test.values - preds.values)))
        return {"rmse": round(rmse, 2), "mae": round(mae, 2)}