from typing import Any, Dict, List, Optional
import logging

import numpy as np
import pandas as pd

try:
    from src.base_model import BaseModel
except ImportError:
    from base_model import BaseModel
try:
    from src.features import prepare_dataset
except ImportError:
    from features import prepare_dataset
try:
    from src.logger import logger
except ImportError:
    from logger import logger


class LinearRegressionModel(BaseModel):
    def __init__(self, alpha: float = 1.0, fit_intercept: bool = True, use_log_target: bool = True, **kwargs):
        super().__init__(model_name="linear_regression")
        from sklearn.linear_model import Ridge
        self._Ridge = Ridge
        self.alpha = alpha
        self.fit_intercept = fit_intercept
        self.use_log_target = use_log_target
        self.weather_cols = ["temp_celsius", "precipitation_mm"]

    def fit(self, df: pd.DataFrame, test_size: float = 0.2, **kwargs):
        X_train, X_test, y_train, y_test, feature_cols = prepare_dataset(
            df,
            test_size=test_size,
            use_log=self.use_log_target,
        )
        self.feature_cols = feature_cols
        self.model = self._Ridge(alpha=self.alpha, fit_intercept=self.fit_intercept)
        self.model.fit(X_train, y_train)
        self.X_test = X_test
        self.y_test = y_test

        df_sorted = df.sort_values("report_date")
        self.last_date = pd.to_datetime(df_sorted["report_date"].iloc[-1])
        self._is_fitted = True
        return self

    def predict(self, df: pd.DataFrame, steps: int = 30, return_format: str = "dict", future_features: Optional[pd.DataFrame] = None, **kwargs) -> List[Dict[str, Any]]:
        if not self._is_fitted or self.feature_cols is None:
            raise ValueError("Model has not been fitted yet. Call fit() first.")

        if future_features is not None:
            feature_cols_to_use = [c for c in self.feature_cols if c in future_features.columns]
            X = future_features[feature_cols_to_use].fillna(0)
            dates = pd.to_datetime(future_features["report_date"]).dt.strftime("%Y-%m-%d")
        else:
            X = df[self.feature_cols].fillna(0)
            if "report_date" in df.columns:
                dates = pd.to_datetime(df["report_date"]).dt.strftime("%Y-%m-%d")
            else:
                dates = [f"future-{i}" for i in range(len(X))]

        raw_preds = self.model.predict(X)

        if self.use_log_target:
            preds = np.expm1(raw_preds)
        else:
            preds = raw_preds

        preds = np.maximum(0, preds).round(2)

        forecast_df = pd.DataFrame({
            "date": dates[:len(preds)],
            "predicted_revenue": preds,
        })

        if return_format == "dict":
            return forecast_df.to_dict(orient="records")
        return forecast_df

    def evaluate(self) -> Dict[str, float]:
        if self.X_test is None or self.y_test is None or not self._is_fitted:
            return {}
        raw_preds = self.model.predict(self.X_test)
        if self.use_log_target:
            preds = np.expm1(raw_preds)
            actuals = np.expm1(self.y_test)
        else:
            preds = raw_preds
            actuals = self.y_test
        rmse = float(np.sqrt(np.mean((actuals.values - preds) ** 2)))
        mae = float(np.mean(np.abs(actuals.values - preds)))
        return {"rmse": round(rmse, 2), "mae": round(mae, 2)}
