from abc import ABC, abstractmethod
from typing import Any, Dict, List, Optional

import numpy as np
import pandas as pd
import joblib
import os


class BaseModel(ABC):
    def __init__(self, model_name: str = "base"):
        self.model_name = model_name
        self.result = None
        self.y_test = None
        self.last_date = None
        self.feature_cols: Optional[List[str]] = None
        self._is_fitted = False

    @abstractmethod
    def fit(self, df: pd.DataFrame, **kwargs):
        pass

    @abstractmethod
    def predict(self, df: pd.DataFrame, **kwargs):
        pass

    def save(self, path: str):
        os.makedirs(os.path.dirname(path), exist_ok=True)
        joblib.dump(self, path)

    @classmethod
    def load(cls, path: str):
        return joblib.load(path)


