# PMUML - PMU Machine Learning Forecasting

Thesis project for time series forecasting using Linear Regression, AMIRA (ARIMA), and SAMIRA (SARIMAX) against PMUAPI (Laravel REST API).

## Project Structure

```
PMUML/
├── __init__.py
├── .env
├── .env.example
├── .gitignore
├── Procfile
├── README.md
├── requirements.txt
├── configs/
│   └── models.yaml          # Hyperparameters & model settings
├── notebooks/
├── scripts/
│   ├── run_amira.py         # Run ARIMA forecast via CLI
│   ├── run_linear.py        # Run Ridge LR forecast via CLI
│   └── run_samira.py        # Run SARIMAX forecast via CLI
├── src/
│   ├── __init__.py
│   ├── config.py            # Centralized configuration (env + YAML)
│   ├── logger.py            # Logging setup
│   ├── base_model.py        # Abstract base for all models
│   ├── pmu_client.py        # REST client for PMUAPI
│   ├── data_loader.py       # Fetch transactions + weather via API
│   ├── features.py          # Feature engineering & dataset prep
│   ├── forecaster.py        # Unified forecasting pipeline
│   ├── evaluation.py        # Thin wrapper around Forecaster
│   ├── weather_service.py   # OpenWeather API integration
│   ├── models/
│   │   ├── __init__.py
│   │   ├── amira.py         # ARIMA model
│   │   ├── samira.py        # SARIMAX model
│   │   └── linear_regression.py  # Ridge regression model
│   ├── api/
│   │   └── __init__.py
│   └── utils/
│       └── __init__.py
├── tests/
├── outputs/
│   ├── models/              # Saved model artifacts (joblib)
│   ├── predictions/         # CSV forecasts
│   └── plots/               # Chart images
├── .venv/
└── venv/
```

## Setup

```bash
cd PMUML
python -m venv .venv
.venv\Scripts\activate    # Windows
# .venv/bin/activate      # Linux/Mac
pip install -r requirements.txt
copy .env.example .env    # Windows
# cp .env.example .env    # Linux/Mac
```

## Usage

### CLI (batch forecasting)

```bash
python scripts/run_amira.py [model_name]
python scripts/run_samira.py [model_name]
python scripts/run_linear.py [model_name]
```

### Web API (gunicorn)

```bash
gunicorn PMUML.src.web:app --bind 0.0.0.0:$PORT --workers 1 --threads 2 --timeout 120
```

Endpoints:
- `GET /` or `GET /health` — Health check
- `GET /predict/all?days=30` — Forecast with all models
- `GET /predict/<model_name>?days=30` — Forecast with single model
- `POST /forecast` — Forecast single model (PMUAPI compatible, `{"model","days","post_to_api"}`)
- `POST /train` — Train all models and post to API (`{"models","days","post_to_api"}`)
- `POST /weather/backfill` — Backfill weather for all ml_features dates
- `POST /weather/sync` — Sync weather for one date (`{"date":"YYYY-MM-DD"}`)
- `GET /weather/status` — Check weather cache status

### Deploy on Render (Cron Job)

1. Push PMUML to GitHub
2. Create a new Cron Job on Render
3. Build Command: `pip install -r requirements.txt`
4. Run Command: `python scripts/run_amira.py`
5. Schedule: daily / weekly
6. Add env vars: `PMU_API_URL`, `PMU_API_TOKEN`

### Manual Training

```bash
# Via API (all models)
curl -X POST http://localhost:8001/train \
  -H "Content-Type: application/json" \
  -d '{"models": ["amira","samira","linear_regression"], "days": 30, "post_to_api": true}'

# Via API (single model)
curl -X POST http://localhost:8001/train \
  -H "Content-Type: application/json" \
  -d '{"models": ["amira"], "days": 30}'

# Via script (saves models + posts to PMUAPI)
python scripts/run_amira.py
python scripts/run_linear.py
python scripts/run_samira.py
```

### Annual/Cron Training

Set up a cron job or Render scheduled job to run:
```bash
python scripts/run_amira.py && python scripts/run_linear.py && python scripts/run_samira.py
```
Or call `POST /train` on PMUML endpoint on a schedule.

### Weather System

Weather is managed by `WeatherManager` in `src/weather_service.py`:

1. **Backfill**: `POST /weather/backfill` fetches historical weather for all `ml_features` dates from PMUAPI `/v1/weather`, caches averages grouped by (month, day)
2. **Sync**: `POST /weather/sync` updates weather for a single date (called after transactions)
3. **Forecast**: For future dates, OpenWeather API provides 5-day forecasts; historical averages fill the remaining days
4. **No duplicates**: PMUAPI's `WeatherData` table enforces unique `weather_date` — one record per day
5. **Used by**: SAMIRA (exog), Linear Regression (future features), AMIRA (not used - univariate)

## Architecture

```
┌─────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  PMUAPI     │────▶│   Forecaster     │────▶│  Model Results   │
│  (Laravel)  │     │  (unified pipe)  │     │  (AMIRA/SAMIRA/  │
└─────────────┘     │                  │     │   LR)            │
                    │  ┌─────────────┐ │     └─────────────────┘
                    │  │ Forecaster  │ │
                    │  │ .forecast() │ │
                    │  └──────┬──────┘ │
                    │         │        │
                    │  ┌──────▼──────┐ │
                    │  │ BaseModel   │ │
                    │  │ (abstract)  │ │
                    │  └──┬───┬───┬──┘ │
                    │     │   │   │    │
                    │  AMIRA SAMIRA LR  │
                    └──────────────────┘

Data Flow:
1. CSV → MlFeatureSeeder → `ml_features` table (temporal + lag + rolling features)
2. `get_historical_features()` → SQL query → DataFrame (NO weather in table)
3. WeatherManager: fetches weather from PMUAPI `/v1/weather` for all ml_features dates → Backfill
4. Weather stored in PMUAPI `WeatherData` table (one per day, no duplicates)
5. SAMIRA training: WeatherManager provides temp_celsius exog for each training date
6. Linear Regression: WeatherManager provides temp/precipitation for future predictions
7. AMIRA: Univariate, no weather used
8. Forecasts posted to PMUAPI `revenue_forecasts` table via `POST /v1/forecasts/generate`
9. UI reads from PMUAPI forecast endpoints
```
```

## Configuration

Edit `configs/models.yaml` for model hyperparameters. Environment variables are loaded from `.env`.

## Database

### ml_features Table
The `ml_features` table (see `PMUAPI/database/migrations/`) stores pre-computed features including:
- Temporal features (year, month, day, day_of_week, quarter)
- Lag features (1d, 7d, 365d)
- Rolling statistics (7d, 30d means)
- Revenue targets and log revenue
- Date completeness flag

**Note**: Weather data is NOT stored in `ml_features`. Weather is managed separately via `WeatherManager`.

### WeatherData Table (PMUAPI)
Weather data is stored in PMUAPI's `WeatherData` table via `WeatherController@store`:
- One record per day (unique `weather_date`)
- Fields: `weather_date`, `rainfall_mm`, `wind_speed`, `temperature`, `source`
- Populated when transactions are created/updated
- Backfilled via `POST /weather/backfill` on PMUML

### revenue_forecasts Table (PMUAPI)
Forecast results stored after training via `POST /v1/forecasts/generate`:
- `forecast_date`, `predicted_revenue`, `season`, `model_version`

| Model | Class | Type | Default Order |
|-------|-------|------|---------------|
| AMIRA | `AMIRAModel` | ARIMA | (1, 1, 1) |
| SAMIRA | `SAMIRAModel` | SARIMAX | (1, 1, 1, 7) weekly |
| Linear Regression | `LinearRegressionModel` | Ridge | alpha=1.0, log target |
