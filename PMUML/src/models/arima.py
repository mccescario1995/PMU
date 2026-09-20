from typing import Any, Dict, List, Optional
import logging

import numpy as np
import pandas as pd

from src.logger import logger
try:
    from src.base_model import BaseModel
except ImportError:
    from base_model import BaseModel


class ARIMAModel(BaseModel):
    def __init__(
        self,
        order: tuple = (1, 1, 1),
        max_training_rows: Optional[int] = None,
        fit_options: Optional[Dict[str, Any]] = None,
        **kwargs,
    ):
        super().__init__(model_name="arima")
        self.order = order
        self.max_training_rows = max_training_rows
        # ARIMA fit() - only pass known safe params (statsmodels ARIMA.fit params)
        self.fit_options = {"method": "lbfgs"}
        if fit_options:
            # Only allow known params for ARIMA.fit()
            allowed = {"method", "maxiter", "ftol", "gtol", "tol", "full_output", "start_params", "cov_type", "cov_kwds"}
            self.fit_options.update({k: v for k, v in fit_options.items() if k in allowed})

    def fit(
        self,
        df: pd.DataFrame,
        target: str = "revenue_target",
        date_col: str = "report_date",
        test_size: float = 0.2,
        **kwargs,
    ):
        from statsmodels.tsa.arima.model import ARIMA as _ARIMA

        data = df.copy()
        data[date_col] = pd.to_datetime(data[date_col])
        series = data.set_index(date_col)[target].asfreq("D")
        series = series.interpolate(method="linear").ffill().fillna(0)

        self.last_date = series.index[-1]
        split = int(len(series) * (1 - test_size))
        train, test = series.iloc[:split], series.iloc[split:]
        self.y_test = test

        if self.max_training_rows is not None and self.max_training_rows > 0 and len(train) > self.max_training_rows:
            train = train.iloc[-self.max_training_rows:]

        try:
            self.result = _ARIMA(train, order=self.order).fit(**self.fit_options)
        except Exception as e:
            logger.warning(f"ARIMA fit failed with order {self.order}, falling back to (1,1,0): {e}")
            self.result = _ARIMA(train, order=(1, 1, 0)).fit(**self.fit_options)

        self._is_fitted = True
        return self

    def predict(self, steps: int = 30, return_format: str = "dict", **kwargs) -> List[Dict[str, Any]]:
        if not self._is_fitted or self.result is None:
            raise ValueError("Model has not been fitted yet. Call fit() first.")

        forecast_series = self.result.forecast(steps=steps)
        future_dates = pd.date_range(
            start=self.last_date + pd.Timedelta(days=1),
            periods=steps,
            freq="D",
        )

        forecast_df = pd.DataFrame({
            "date": future_dates.strftime("%Y-%m-%d"),
            "predicted_revenue": np.maximum(0, forecast_series.values).round(2),
        })

        if return_format == "dict":
            return forecast_df.to_dict(orient="records")
        return forecast_df

    def evaluate(self) -> Dict[str, float]:
        if self.y_test is None or self.result is None or not self._is_fitted:
            return {}
        preds = self.result.forecast(steps=len(self.y_test))
        rmse = float(np.sqrt(np.mean((self.y_test.values - preds.values) ** 2)))
        mae = float(np.mean(np.abs(self.y_test.values - preds.values)))
        return {"rmse": round(rmse, 2), "mae": round(mae, 2)}
