from datetime import date
from typing import Optional

import pandas as pd

from pmu_client import PMUClient


def load_transactions(client: PMUClient, start: date, end: date) -> pd.DataFrame:
    data = client.get_transactions(start, end)
    if not data:
        return pd.DataFrame(columns=["date", "revenue"])

    df = pd.DataFrame(data)
    df["date"] = pd.to_datetime(df["transaction_date"]).dt.date
    df = df.rename(columns={"total_amount": "revenue"})
    df["revenue"] = pd.to_numeric(df["revenue"], errors="coerce")
    return df[["date", "revenue"]].sort_values("date").reset_index(drop=True)


def load_weather(client: PMUClient, start: date, end: date) -> pd.DataFrame:
    data = client.get_weather(start, end)
    if not data:
        return pd.DataFrame(columns=["date", "temperature", "rainfall"])

    df = pd.DataFrame(data)
    df["date"] = pd.to_datetime(df["weather_date"]).dt.date
    return df[["date", "temperature", "rainfall"]].sort_values("date").reset_index(drop=True)


def merge_data(transactions: pd.DataFrame, weather: pd.DataFrame) -> pd.DataFrame:
    if transactions.empty:
        return transactions

    df = transactions.copy()
    if not weather.empty:
        df = df.merge(weather, on="date", how="left")
    return df
