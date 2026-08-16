# PMUML - PMU Machine Learning Forecasting

Thesis project for time series forecasting using Linear Regression, AMIRA, and SAMIRA against PMUAPI (Laravel REST API).

## Project Structure

```
PMUML/
├── src/
│   ├── pmu_client.py      # REST client for PMUAPI
│   ├── data_loader.py     # Fetch transactions + weather
│   ├── features.py        # Feature engineering
│   ├── models/
│   │   ├── linear_regression.py
│   │   ├── amira.py
│   │   └── samira.py
│   └── evaluation.py      # Metrics and diagnostics
├── configs/
│   └── models.yaml        # Hyperparameters
├── outputs/
│   ├── models/            # Saved models
│   ├── predictions/       # CSV forecasts
│   └── plots/             # Charts
├── scripts/
│   ├── run_linear.py
│   ├── run_amira.py
│   └── run_samira.py
├── notebooks/
├── tests/
├── .env.example
├── requirements.txt
└── README.md
```

## Setup

```bash
python -m venv .venv
source .venv/bin/activate  # Windows: .venv\Scripts\activate
pip install -r requirements.txt
cp .env.example .env
```

## Usage

```bash
python scripts/run_amira.py
```

## Deploy on Render (Cron Job)

1. Push PMUML to GitHub
2. Create a new Cron Job on Render
3. Build Command: `pip install -r requirements.txt`
4. Run Command: `python scripts/run_amira.py`
5. Schedule: daily / weekly
6. Add env vars: `PMU_API_URL`, `PMU_API_TOKEN`
