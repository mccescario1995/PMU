from typing import Optional

import numpy as np
from sklearn.linear_model import Ridge
from sklearn.metrics import mean_absolute_error, mean_squared_error

from features import prepare_dataset


class LinearRegressionModel:
    def __init__(self, alpha: float = 1.0, fit_intercept: bool = True):
        self.model = Ridge(alpha=alpha, fit_intercept=fit_intercept)
        self.feature_cols = None

    def fit(self, df, **kwargs):
        X_train, X_test, y_train, y_test, feature_cols = prepare_dataset(df)
        self.feature_cols = feature_cols
        self.model.fit(X_train, y_train)
        self.X_test = X_test
        self.y_test = y_test
        return self

    def predict(self, df, **kwargs):
        X, _, _, _, _ = prepare_dataset(df, test_size=0.0)
        X = X[self.feature_cols]
        return self.model.predict(X)

    def evaluate(self):
        if self.X_test is None or self.y_test is None:
            return {}
        preds = self.model.predict(self.X_test)
        return {
            "rmse": float(np.sqrt(mean_squared_error(self.y_test, preds))),
            "mae": float(mean_absolute_error(self.y_test, preds)),
        }
