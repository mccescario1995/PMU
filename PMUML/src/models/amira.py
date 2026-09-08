import numpy as np
import pandas as pd
from statsmodels.tsa.arima.model import ARIMA
from sklearn.metrics import mean_absolute_error, mean_squared_error


class AMIRAModel:
    def __init__(self, order=(1, 1, 1)):
        self.order = order
        self.result = None
        self.y_test = None

    def fit(self, df, target: str = "revenue", test_size: float = 0.2, **kwargs):
        series = df.set_index("date")[target].asfreq("D")
        series = series.interpolate(method="linear")

        split = int(len(series) * (1 - test_size))
        train, test = series.iloc[:split], series.iloc[split:]
        self.y_test = test

        try:
            self.result = ARIMA(train, order=self.order).fit()
        except Exception:
            self.result = ARIMA(train, order=(1, 1, 0)).fit()

        return self

    def predict(self, df, steps: int = 30, **kwargs):
        forecast = self.result.forecast(steps=steps)
        return forecast

    def evaluate(self):
        if self.y_test is None:
            return {}
        preds = self.result.forecast(steps=len(self.y_test))
        return {
            "rmse": float(np.sqrt(mean_squared_error(self.y_test, preds))),
            "mae": float(mean_absolute_error(self.y_test, preds)),
        }
