import os
import sys

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))

try:
    from dotenv import load_dotenv
    env_path = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".env"))
    load_dotenv(env_path)
except Exception:
    pass

from src.pmu_client import PMUClient
from src.forecaster import Forecaster, Config


def run_model(model_name: str):
    client = PMUClient(
        base_url=os.getenv("PMU_API_URL", "http://localhost:8000"),
        token=os.getenv("PMU_API_TOKEN", ""),
    )
    config = Config()
    forecaster = Forecaster(config=config, client=client)
    days = config.forecast_days

    result = forecaster.forecast(models=[model_name], days=days, client=client)
    forecast = result.get(model_name)

    if forecast is None or forecast.error:
        print(f"[ERROR] {model_name} forecast failed: {forecast.error if forecast else 'Unknown'}")
        return

    print(f"Metrics: {forecast.metrics}")
    print(f"Forecasts: {len(forecast.forecasts)} entries")

    forecaster.save_all()
    print(f"Models saved to outputs/models/")

    model_version = os.getenv("MODEL_VERSION", f"{model_name}-v1")
    if forecast.forecasts:
        posted = client.post_forecast_batch(forecast.forecasts, model_version=model_version)
        print(f"Posted {len(posted)} forecasts to PMUAPI.")


if __name__ == "__main__":
    model = sys.argv[1] if len(sys.argv) > 1 else "sarima"
    run_model(model)
