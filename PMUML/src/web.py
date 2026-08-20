import os
import sys
import json
from datetime import date, timedelta

sys.path.insert(0, os.path.abspath(os.path.dirname(__file__)))

from flask import Flask, request, jsonify
from dotenv import load_dotenv
import yaml

load_dotenv()

from pmu_client import PMUClient
from evaluation import run_linear, run_amira, run_samira

app = Flask(__name__)

MODELS = {
    "linear_regression": run_linear,
    "amira": run_amira,
    "samira": run_samira,
}


@app.route("/health", methods=["GET"])
def health():
    return jsonify({"status": "ok"})


@app.route("/forecast", methods=["POST"])
def forecast():
    data = request.get_json(force=True)
    model_name = data.get("model", "amira")
    days = int(data.get("days", 30))

    if model_name not in MODELS:
        return jsonify({"error": f"Unknown model: {model_name}"}), 400

    client = PMUClient(
        base_url=os.getenv("PMU_API_URL", "http://localhost:8000"),
        token=os.getenv("PMU_API_TOKEN", ""),
    )

    try:
        result = MODELS[model_name](client)
        result["forecasts"] = result["forecasts"][:days]
        return jsonify(result)
    except Exception as e:
        return jsonify({"error": str(e)}), 500


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=int(os.getenv("PORT", 8000)))
