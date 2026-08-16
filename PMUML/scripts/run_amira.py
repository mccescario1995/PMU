import os
import sys

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "src")))

try:
    from dotenv import load_dotenv
    env_path = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".env"))
    load_dotenv(env_path)
except Exception:
    pass

from pmu_client import PMUClient
from evaluation import run_amira


def main():
    client = PMUClient(
        base_url=os.getenv("PMU_API_URL", "http://localhost:8000"),
        token=os.getenv("PMU_API_TOKEN", ""),
    )
    model_version = os.getenv("MODEL_VERSION", "amira-v1")

    result = run_amira(client)
    print("Metrics:", result["metrics"])
    print("CSV:", result["csv_path"])
    print("Plot:", result["plot_path"])

    posted = client.post_forecast_batch(result["forecasts"], model_version=model_version)
    print(f"Posted {len(posted)} forecasts to PMUAPI.")


if __name__ == "__main__":
    main()
