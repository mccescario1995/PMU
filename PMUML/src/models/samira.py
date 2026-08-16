import numpy as np
import pandas as pd
from statsmodels.tsa.statespace.sarimax import SARIMAX
from sklearn.metrics import mean_absolute_error, mean_squared_error


class SAMIRAModel:
    def __init__(self, order=(1, 1, 1), seasonal_order=(1, 1, 1, 12)):
        self.order = order
        self.seasonal_order = seasonal_order
        self.result = None
        self.y_test = None

    def fit(self, df, target: str = "revenue", exog_col: Optional[str] = None, test_size: float = 0.2):
        series = df.set_index("date")[target].asfreq("D")
        series = series.interpolate(method="linear")

        exog = None
        if exog_col and exog_col in df.columns:
            exog = df.set_index("date")[exog_col].asfreq("D").interpolate(method="linear")

        split = int(len(series) * (1 - test_size))
        train, test = series.iloc[:split], series.iloc[split:]
        self.y_test = test

        train_exog = exog.iloc[:split] if exog is not None else None

        try:
            self.result = SARIMAX(train, exog=train_exog, order=self.order, seasonal_order=self.seasonal_order).fit(disp=False)
        except Exception:
            self.result = SARIMAX(train, order=(1, 1, 0)).fit(disp=False)

        return self

    def predict(self, df, steps: int = 30, exog_col: Optional[str] = None):
        exog = None
        if exog_col and exog_col in df.columns:
            exog = df.set_index("date")[exog_col].asfreq("D").interpolate(method="linear")
        forecast = self.result.get_forecast(steps=steps, exog=exog)
        return forecast.predicted_mean

    def evaluate(self):
        if self.y_test is None:
            return {}
        preds = self.result.get_forecast(steps=len(self.y_test)).predicted_mean
        return {
            "rmse": float(np.sqrt(mean_squared_error(self.y_test, preds))),
            "mae": float(mean_absolute_error(self.y_test, preds)),
        }
