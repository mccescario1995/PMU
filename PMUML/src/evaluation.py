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


def run_amira(client=None, config=None):
    forecaster = Forecaster(config=config or Config())
    days = int(config or Config().forecast_days) if config else 30
    results = forecaster.forecast(models=["amira"], days=days, client=client)
    result = results.get("amira")
    if result:
        return {
            "model": "amira",
            "metrics": result.metrics,
            "forecasts": result.forecasts,
            "csv_path": "",
            "plot_path": "",
        }
    return {"model": "amira", "metrics": {}, "forecasts": [], "csv_path": "", "plot_path": ""}


def run_samira(client=None, config=None):
    forecaster = Forecaster(config=config or Config())
    days = int(config or Config().forecast_days) if config else 30
    results = forecaster.forecast(models=["samira"], days=days, client=client)
    result = results.get("samira")
    if result:
        return {
            "model": "samira",
            "metrics": result.metrics,
            "forecasts": result.forecasts,
            "csv_path": "",
            "plot_path": "",
        }
    return {"model": "samira", "metrics": {}, "forecasts": [], "csv_path": "", "plot_path": ""}
