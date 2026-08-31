from typing import Optional
from datetime import date
import httpx


class PMUClient:
    def __init__(self, base_url: str, token: str):
        self.base_url = base_url.rstrip("/")
        self.headers = {"Authorization": f"Bearer {token}"}

    def _get(self, path: str, params: Optional[dict] = None) -> dict:
        with httpx.Client(base_url=self.base_url, headers=self.headers, timeout=30) as client:
            r = client.get(path, params=params)
            r.raise_for_status()
            return r.json()

    def _post(self, path: str, payload: dict) -> dict:
        with httpx.Client(base_url=self.base_url, headers=self.headers, timeout=30) as client:
            r = client.post(path, json=payload)
            r.raise_for_status()
            return r.json()

    def get_transactions(self, start: date, end: date) -> list[dict]:
        with httpx.Client(base_url=self.base_url, headers=self.headers, timeout=60) as client:
            r = client.get("/v1/transactions")
            r.raise_for_status()
            data = r.json()
        if isinstance(data, dict):
            data = data.get("data", data)
        return [
            item for item in data
            if isinstance(item, dict) and start <= date.fromisoformat(item["transaction_date"][:10]) <= end
        ]

    def get_weather(self, start: date, end: date) -> list[dict]:
        with httpx.Client(base_url=self.base_url, headers=self.headers, timeout=60) as client:
            r = client.get("/v1/weather")
            r.raise_for_status()
            data = r.json()
        if isinstance(data, dict):
            data = data.get("data", data)
        return [
            item for item in data
            if isinstance(item, dict) and start <= date.fromisoformat(item["weather_date"][:10]) <= end
        ]

    def get_forecasts(self) -> list[dict]:
        return self._get("/v1/forecasts")

    def post_forecast(self, forecast_date: str, predicted_revenue: float, model_version: str, season: Optional[str] = None) -> dict:
        payload = {
            "forecast_date": forecast_date,
            "predicted_revenue": predicted_revenue,
            "model_version": model_version,
        }
        if season:
            payload["season"] = season
        return self._post("/v1/forecasts/generate", payload)

    def post_forecast_batch(self, forecasts: list[dict], model_version: str) -> list[dict]:
        results = []
        for f in forecasts:
            results.append(
                self.post_forecast(
                    forecast_date=f["date"],
                    predicted_revenue=f["predicted_revenue"],
                    model_version=model_version,
                    season=f.get("season"),
                )
            )
        return results
