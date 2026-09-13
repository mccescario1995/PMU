import logging
import os
from collections import defaultdict
from datetime import date, timedelta
from typing import Any, Dict, List, Optional, Tuple

import pandas as pd
import requests

from src.logger import logger

LATITUDE = os.getenv("LATITUDE", "13.5049")
LONGITUDE = os.getenv("LONGITUDE", "123.0434")


_WMO_CODES: Dict[int, str] = {
    0: "Clear",
    1: "Mainly Clear",
    2: "Partly Cloudy",
    3: "Overcast",
    45: "Fog",
    48: "Depositing Rime Fog",
    51: "Light Drizzle",
    53: "Moderate Drizzle",
    55: "Dense Drizzle",
    61: "Slight Rain",
    63: "Moderate Rain",
    65: "Heavy Rain",
    71: "Slight Snow",
    73: "Moderate Snow",
    75: "Heavy Snow",
    80: "Slight Showers",
    81: "Moderate Showers",
    82: "Violent Showers",
    95: "Thunderstorm",
    96: "Thunderstorm & Hail",
    99: "Heavy Thunderstorm & Hail",
}


def _wmo_to_weather_main(code: int) -> str:
    if code in _WMO_CODES:
        return _WMO_CODES[code]
    if code <= 3:
        return "Clear"
    if code <= 48:
        return "Cloudy"
    if code <= 77:
        return "Rain"
    return "Clear"


def _extract_openmeteo_forecast(data: dict) -> Dict[str, Dict[str, Any]]:
    weather_by_date: Dict[str, Dict[str, Any]] = {}
    current = data.get("current", {})
    hourly = data.get("hourly", {})
    times = hourly.get("time", [])
    temps = hourly.get("temperature_2m", [])
    precips = hourly.get("precipitation", [])
    codes = hourly.get("weather_code", [])

    if current and "time" in current:
        try:
            date_str = current["time"][:10] if isinstance(current.get("time"), str) else current["time"].get("time", "")[:10]
            if date_str:
                weather_by_date[date_str] = {
                    "temp_celsius": current.get("temperature_2m", 25.0),
                    "precipitation_mm": current.get("precipitation", 0.0),
                    "weather_main": _wmo_to_weather_main(current.get("weather_code", 0)),
                }
        except Exception:
            pass

    for i, date_str in enumerate(times):
        date_only = date_str[:10] if isinstance(date_str, str) else str(date_str)[:10]
        if date_only in weather_by_date:
            continue
        weather_by_date[date_only] = {
            "temp_celsius": temps[i] if i < len(temps) else 25.0,
            "precipitation_mm": precips[i] if i < len(precips) else 0.0,
            "weather_main": _wmo_to_weather_main(codes[i] if i < len(codes) else 0),
        }

    return weather_by_date


class WeatherManager:
    def __init__(self, client: Optional[Any] = None):
        self.client = client
        self._historical_avg: Dict[Tuple[int, int], Dict[str, float]] = {}
        self._recent_weather: Dict[str, Dict[str, Any]] = {}
        self._backfilled_dates: set = set()

    def load_historical(self, days: int = 730) -> None:
        if self.client is not None:
            self._load_from_client(days)
        else:
            logger.warning("No client provided for historical weather. Using neutral averages.")
            self._historical_avg = {}

    def _load_from_client(self, days: int) -> None:
        end = date.today()
        start = end - timedelta(days=days)
        from datetime import date as date_cls
        chunk_size = 365
        all_weather = []
        current_start = start
        while current_start < end:
            current_end = min(current_start + timedelta(days=chunk_size), end)
            try:
                records = self.client.get_weather(current_start, current_end)
                all_weather.extend(records)
            except Exception as e:
                logger.warning(f"Failed to load historical weather {current_start} to {current_end}: {e}")
                break
            current_start = current_end

        by_date: Dict[Tuple[int, int], List[Dict[str, Any]]] = defaultdict(list)
        for item in all_weather:
            date_str = item.get("weather_date", "")[:10]
            if not date_str:
                continue
            try:
                d = date.fromisoformat(date_str)
                temp = float(item.get("temperature", 25.0))
                rain = float(item.get("rainfall_mm", 0.0))
                source = item.get("source", "")
                if "onecall" in source.lower() or "forecast" in source.lower():
                    cond = "Clear"
                else:
                    cond = item.get("condition", "Clear")
                by_date[(d.month, d.day)].append({
                    "temp_celsius": temp,
                    "precipitation_mm": rain,
                    "weather_main": cond,
                })
            except (ValueError, TypeError):
                continue

        self._historical_avg = {}
        for key, records in by_date.items():
            if records:
                self._historical_avg[key] = {
                    "temp_celsius": sum(r["temp_celsius"] for r in records) / len(records),
                    "precipitation_mm": sum(r["precipitation_mm"] for r in records) / len(records),
                    "weather_main": records[0]["weather_main"],
                }
        logger.info(f"WeatherManager: loaded averages for {len(self._historical_avg)} date patterns from {len(all_weather)} records")

    def fetch_openweather(self, days: int) -> Dict[str, Dict[str, Any]]:
        return self.fetch_openmeteo(days)

    def fetch_openmeteo(self, days: int) -> Dict[str, Dict[str, Any]]:
        weather_by_date: Dict[str, Dict[str, Any]] = {}
        today = date.today()
        for day_offset in range(0, days, 7):
            chunk = min(7, days - day_offset)
            try:
                url = (
                    f"https://api.open-meteo.com/v1/forecast"
                    f"?latitude={LATITUDE}&longitude={LONGITUDE}"
                    f"&current=temperature_2m,wind_speed_10m"
                    f"&hourly=temperature_2m,precipitation,weather_code"
                    f"&forecast_days={chunk}"
                    f"&timezone=Asia/Manila"
                )
                response = requests.get(
                    url, timeout=15, headers={
                        "User-Agent": "PMU/1.0",
                        "Accept-Encoding": "identity",
                    },
                )
                if response.status_code != 200:
                    logger.warning(f"Open-Meteo status {response.status_code}")
                    break
                data = response.json()
                chunk_weather = _extract_openmeteo_forecast(data)
                weather_by_date.update(chunk_weather)
            except Exception as e:
                logger.warning(f"Open-Meteo Error: {e}")
                break
        return weather_by_date

    def get_forecast_weather(self, start_date: date, days: int) -> pd.DataFrame:
        openweather = self.fetch_openweather(days)

        rows = []
        for i in range(days):
            d = start_date + timedelta(days=i)
            date_str = d.isoformat()
            key = (d.month, d.day)

            if date_str in openweather:
                rows.append({"date": date_str, **openweather[date_str]})
            elif key in self._historical_avg:
                avg = self._historical_avg[key]
                rows.append({"date": date_str, **avg})
            else:
                rows.append({
                    "date": date_str,
                    "temp_celsius": 25.0,
                    "precipitation_mm": 0.0,
                    "weather_main": "Clear",
                })

        df = pd.DataFrame(rows)
        return df

    def get_weather_for_date(self, d: date) -> Dict[str, float]:
        date_str = d.isoformat()
        key = (d.month, d.day)
        if date_str in self._recent_weather:
            return self._recent_weather[date_str]
        if key in self._historical_avg:
            return self._historical_avg[key]
        return {"temp_celsius": 25.0, "precipitation_mm": 0.0, "weather_main": "Clear"}

    def backfill(self, dates: List[str]) -> Dict[str, Dict[str, Any]]:
        result: Dict[str, Dict[str, Any]] = {}
        missing = [d for d in dates if d not in self._backfilled_dates]
        if not missing:
            logger.info(f"Weather backfill: all {len(dates)} dates already cached")
            for d in dates:
                result[d] = self._recent_weather.get(d, self.get_weather_for_date(date.fromisoformat(d)))
            return result

        logger.info(f"Weather backfill: fetching {len(missing)} dates")
        unique_dates = sorted(set(missing), key=lambda d: date.fromisoformat(d))
        for d_str in unique_dates:
            try:
                d = date.fromisoformat(d_str)
                weather = self._fetch_for_date(d)
                if weather:
                    result[d_str] = weather
                    self._recent_weather[d_str] = weather
                    self._backfilled_dates.add(d_str)
            except Exception as e:
                logger.warning(f"Weather backfill failed for {d_str}: {e}")
                result[d_str] = {"temp_celsius": 25.0, "precipitation_mm": 0.0, "weather_main": "Clear"}

        logger.info(f"Weather backfill: completed for {len(result)} dates")
        return result

    def _fetch_for_date(self, d: date) -> Optional[Dict[str, Any]]:
        from datetime import date as date_cls
        today = date_cls.today()
        is_future = d >= today

        if is_future:
            return self._fetch_openmeteo_for_date(d)

        if self.client is not None:
            weather = self._fetch_from_client(d)
            if weather:
                return weather

        key = (d.month, d.day)
        if key in self._historical_avg:
            return self._historical_avg[key].copy()

        return {"temp_celsius": 25.0, "precipitation_mm": 0.0, "weather_main": "Clear"}

    def _fetch_openmeteo_for_date(self, d: date) -> Optional[Dict[str, Any]]:
        try:
            url = (
                f"https://api.open-meteo.com/v1/forecast"
                f"?latitude={LATITUDE}&longitude={LONGITUDE}"
                f"&current=temperature_2m,wind_speed_10m"
                f"&hourly=temperature_2m,precipitation,weather_code"
                f"&forecast_days=1"
                f"&timezone=Asia/Manila"
            )
            response = requests.get(
                url, timeout=15, headers={
                    "User-Agent": "PMU/1.0",
                    "Accept-Encoding": "identity",
                },
            )
            if response.status_code != 200:
                return None
            data = response.json()
            current = data.get("current", {})
            if not current or "time" not in current:
                return None
            try:
                date_str = current["time"][:10] if isinstance(current["time"], str) else str(current["time"])[:10]
                if date_str != d.isoformat():
                    return None
                return {
                    "temp_celsius": current.get("temperature_2m", 25.0),
                    "precipitation_mm": current.get("precipitation", 0.0),
                    "weather_main": _wmo_to_weather_main(current.get("weather_code", 0)),
                }
            except Exception as e:
                logger.warning(f"Open-Meteo fetch for {d}: {e}")
        except Exception as e:
            logger.warning(f"Open-Meteo fetch for {d}: {e}")
        return None

    def _fetch_from_client(self, d: date) -> Optional[Dict[str, Any]]:
        try:
            from datetime import date as date_cls
            start = d - timedelta(days=1)
            end = d + timedelta(days=1)
            records = self.client.get_weather(start, end)
            for item in records:
                if item.get("weather_date", "")[:10] == d.isoformat():
                    return {
                        "temp_celsius": float(item.get("temperature", 25.0)),
                        "precipitation_mm": float(item.get("rainfall_mm", 0.0)),
                        "weather_main": item.get("source", "Clear") if "forecast" in item.get("source", "").lower() else item.get("condition", "Clear"),
                    }
        except Exception as e:
            logger.warning(f"Client fetch weather for {d}: {e}")
        return None

    def sync_weather_for_date(self, d: date) -> bool:
        weather = self._fetch_for_date(d)
        if weather is None:
            return False
        date_str = d.isoformat()
        self._recent_weather[date_str] = weather
        self._backfilled_dates.add(date_str)
        key = (d.month, d.day)
        if key not in self._historical_avg:
            self._historical_avg[key] = weather.copy()
        logger.info(f"Weather synced for {date_str}: {weather}")
        return True

    def get_cached_weather(self) -> Dict[str, Dict[str, Any]]:
        return {**self._recent_weather}

    def get_backfill_status(self) -> dict:
        return {
            "dates_cached": len(self._backfilled_dates),
            "historical_patterns": len(self._historical_avg),
            "recent_entries": len(self._recent_weather),
        }
