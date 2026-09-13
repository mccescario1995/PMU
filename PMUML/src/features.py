import numpy as np
import pandas as pd
from typing import Optional


def prepare_dataset(
    df: pd.DataFrame,
    test_size: float = 0.2,
    use_log: bool = True,
    date_col: str = "report_date",
    target_col: Optional[str] = None,
) -> tuple:
    data = df.sort_values(date_col).copy()

    if target_col is None:
        target_col = "log_revenue" if use_log and "log_revenue" in data.columns else "revenue_target"

    exclude_cols = [date_col, "revenue_target", "log_revenue", "updated_at", "created_at"]
    feature_cols = [c for c in data.columns if c not in exclude_cols]

    X = data[feature_cols].fillna(0)
    y = data[target_col].fillna(0)

    if test_size > 0.0:
        split = int(len(data) * (1 - test_size))
        X_train, X_test = X.iloc[:split], X.iloc[split:]
        y_train, y_test = y.iloc[:split], y.iloc[split:]
    else:
        X_train, X_test = X, pd.DataFrame(columns=feature_cols)
        y_train, y_test = y, pd.Series(dtype=y.dtype)

    return X_train, X_test, y_train, y_test, feature_cols


def generate_future_features(
    last_row: pd.Series,
    steps: int,
    weather_df: Optional[pd.DataFrame] = None,
) -> pd.DataFrame:
    rows = []
    last_date = pd.to_datetime(last_row["report_date"])

    weather_by_date: Dict[str, Dict[str, Any]] = {}
    if weather_df is not None and "date" in weather_df.columns:
        for _, wrow in weather_df.iterrows():
            weather_by_date[wrow["date"]] = wrow.to_dict()

    for i in range(1, steps + 1):
        future_date = last_date + pd.Timedelta(days=i)
        date_str = future_date.strftime("%Y-%m-%d")
        row = {"report_date": date_str}
        for col in last_row.index:
            if col != "report_date":
                row[col] = last_row[col]
        if date_str in weather_by_date:
            for wcol in ["temp_celsius", "precipitation_mm", "weather_main"]:
                if wcol in weather_by_date[date_str]:
                    row[wcol] = weather_by_date[date_str][wcol]
        rows.append(row)
    return pd.DataFrame(rows)
