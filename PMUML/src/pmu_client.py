import os
from datetime import date
from typing import Optional

import httpx


class PMUClient:
    def __init__(self, base_url: str, token: str = "", service_secret: str = ""):
        self.base_url = base_url.rstrip("/")
        self.service_secret = service_secret
        self.uses_internal_ml_data = bool(service_secret)
        self.headers = {}
        if token:
            self.headers["Authorization"] = f"Bearer {token}"
        if service_secret:
            self.headers["X-PMUML-Secret"] = service_secret
        self._timeout = int(os.getenv("HTTP_TIMEOUT", "60"))
        self._ml_data_by_date = {}

    def _get(self, path: str, params: Optional[dict] = None) -> dict:
        with httpx.Client(base_url=self.base_url, headers=self.headers, timeout=self._timeout) as client:
            r = client.get(path, params=params)
            r.raise_for_status()
            return r.json()

    def _post(self, path: str, payload: dict) -> dict:
        with httpx.Client(base_url=self.base_url, headers=self.headers, timeout=self._timeout) as client:
            r = client.post(path, json=payload)
            r.raise_for_status()
            return r.json()

    def get_transactions(self, start: date, end: date) -> list[dict]:
        with httpx.Client(base_url=self.base_url, headers=self.headers, timeout=self._timeout) as client:
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
        if self.service_secret:
            return self._get_internal_weather(start, end)

        with httpx.Client(base_url=self.base_url, headers=self.headers, timeout=self._timeout) as client:
            r = client.get("/v1/weather")
            r.raise_for_status()
            data = r.json()
        if isinstance(data, dict):
            data = data.get("data", data)
        return [
            item for item in data
            if isinstance(item, dict) and start <= date.fromisoformat(item["weather_date"][:10]) <= end
        ]

    def _get_internal_weather(self, start: date, end: date) -> list[dict]:
        start_key = start.isoformat()
        end_key = end.isoformat()
        rows = [
            row
            for row_date, row in self._ml_data_by_date.items()
            if start_key <= row_date <= end_key
        ]
        if not rows:
            rows = self.get_ml_data(start, end)

        return [
            {
                "weather_date": row.get("report_date"),
                "temperature": row.get("temp_celsius"),
                "rainfall_mm": row.get("precipitation_mm"),
                "wind_speed": row.get("wind_speed"),
                "source": "internal_ml_data",
            }
            for row in rows
            if row.get("report_date")
        ]

    def get_ml_data(self, start: date, end: date) -> list[dict]:
        data = self._post(
            "/v1/internal/ml-data",
            {"start_date": start.isoformat(), "end_date": end.isoformat()},
        )
        if isinstance(data, dict):
            data = data.get("data", data)
        rows = [item for item in data if isinstance(item, dict)]
        for row in rows:
            row_date = str(row.get("report_date", ""))[:10]
            if row_date:
                self._ml_data_by_date[row_date] = row
        return rows

    def _paginate(self, path: str, params: Optional[dict] = None, per_page: int = 200) -> list[dict]:
        records: list[dict] = []
        page = 1
        while True:
            p = dict(params or {})
            p["per_page"] = per_page
            p["page"] = page
            data = self._get(path, params=p)
            if isinstance(data, dict):
                items = data.get("data", [])
                meta = data.get("meta", {})
            elif isinstance(data, list):
                items = data
                meta = {}
            else:
                items = []
                meta = {}
            records.extend(items)
            last_page = meta.get("last_page", page)
            if not items or page >= last_page:
                break
            page += 1
        return records

    def get_transaction_revenue(self, per_page: int = 200) -> list[dict]:
        return self._paginate("/v1/transaction-revenue", {}, per_page=per_page)

    def get_transaction_revenue_features(self, per_page: int = 200) -> list[dict]:
        return self._paginate("/v1/transaction-revenue-features", {}, per_page=per_page)

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
