from src.forecaster import Forecaster
from src.config import Config


def run_linear(client=None, config=None):
    forecaster = Forecaster(config=config or Config())
    days = int(config or Config().forecast_days) if config else 30
    results = forecaster.forecast(models=["linear_regression"], days=days, client=client)
    result = results.get("linear_regression")
    if result:
        return {
            "model": "linear_regression",
            "metrics": result.metrics,
            "forecasts": result.forecasts,
            "csv_path": "",
            "plot_path": "",
        }
    return {"model": "linear_regression", "metrics": {}, "forecasts": [], "csv_path": "", "plot_path": ""}


def run_arima(client=None, config=None):
    forecaster = Forecaster(config=config or Config())
    days = int(config or Config().forecast_days) if config else 30
    results = forecaster.forecast(models=["arima"], days=days, client=client)
    result = results.get("arima")
    if result:
        return {
            "model": "arima",
            "metrics": result.metrics,
            "forecasts": result.forecasts,
            "csv_path": "",
            "plot_path": "",
        }
    return {"model": "arima", "metrics": {}, "forecasts": [], "csv_path": "", "plot_path": ""}


def run_sarima(client=None, config=None):
    forecaster = Forecaster(config=config or Config())
    days = int(config or Config().forecast_days) if config else 30
    results = forecaster.forecast(models=["sarima"], days=days, client=client)
    result = results.get("sarima")
    if result:
        return {
            "model": "sarima",
            "metrics": result.metrics,
            "forecasts": result.forecasts,
            "csv_path": "",
            "plot_path": "",
        }
    return {"model": "sarima", "metrics": {}, "forecasts": [], "csv_path": "", "plot_path": ""}
