import pandas as pd
from sklearn.model_selection import train_test_split


def create_lag_features(df: pd.DataFrame, target: str = "revenue", lags: int = 7) -> pd.DataFrame:
    df = df.copy()
    for lag in range(1, lags + 1):
        df[f"lag_{lag}"] = df[target].shift(lag)
    return df


def create_rolling_features(df: pd.DataFrame, target: str = "revenue", window: int = 7) -> pd.DataFrame:
    df = df.copy()
    df["rolling_mean"] = df[target].rolling(window=window, min_periods=1).mean()
    df["rolling_std"] = df[target].rolling(window=window, min_periods=1).std()
    return df


def encode_season(df: pd.DataFrame, date_col: str = "date") -> pd.DataFrame:
    df = df.copy()
    df["month"] = pd.to_datetime(df[date_col]).dt.month
    df["quarter"] = pd.to_datetime(df[date_col]).dt.quarter
    df["is_peak"] = (df["month"] >= 1) & (df["month"] <= 6)
    return df


def prepare_dataset(df: pd.DataFrame, target: str = "revenue", lags: int = 7, test_size: float = 0.2):
    df = encode_season(df)
    df = create_lag_features(df, target=target, lags=lags)
    df = create_rolling_features(df, target=target)
    df = df.dropna().reset_index(drop=True)

    feature_cols = [c for c in df.columns if c not in [target, "date"]]
    X = df[feature_cols]
    y = df[target]

    if test_size > 0:
        X_train, X_test, y_train, y_test = train_test_split(X, y, shuffle=False, test_size=test_size)
        return X_train, X_test, y_train, y_test, feature_cols

    return X, None, y, None, feature_cols
