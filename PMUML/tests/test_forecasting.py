import os
import sys
from dotenv import load_dotenv

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))
load_dotenv(os.path.join(os.path.dirname(__file__), "..", ".env"))

from src.features import prepare_dataset, generate_future_features
from src.models.arima import ARIMAModel
from src.models.sarima import SARIMAModel
from src.models.linear_regression import LinearRegressionModel
from src.pmu_client import PMUClient
from src.forecaster import Forecaster, ForecastResult, Config
from src.weather_service import WeatherManager

import pandas as pd
import numpy as np
from datetime import date
from unittest.mock import patch


def test_prepare_dataset():
    df = pd.DataFrame({
        "report_date": ["2024-01-01", "2024-01-02", "2024-01-03", "2024-01-04", "2024-01-05"],
        "revenue_target": [100, 150, 200, 180, 220],
        "feature_a": [1, 2, 3, 4, 5],
        "feature_b": [10, 20, 30, 40, 50],
    })
    X_train, X_test, y_train, y_test, feature_cols = prepare_dataset(df, test_size=0.4)
    assert len(X_train) == 3
    assert len(X_test) == 2
    assert "report_date" not in feature_cols
    assert "revenue_target" not in feature_cols
    print("PASS: test_prepare_dataset")


def test_prepare_dataset_no_test():
    df = pd.DataFrame({
        "report_date": ["2024-01-01", "2024-01-02"],
        "revenue_target": [100, 150],
        "feature_a": [1, 2],
    })
    X_train, X_test, y_train, y_test, feature_cols = prepare_dataset(df, test_size=0.0)
    assert len(X_train) == 2
    assert X_test.empty
    print("PASS: test_prepare_dataset_no_test")


def test_generate_future_features():
    last_row = pd.Series({
        "report_date": "2024-01-01",
        "feature_a": 5,
        "revenue_target": 100,
    })
    weather_df = pd.DataFrame({
        "date": ["2024-01-02", "2024-01-03"],
        "temp_celsius": [26.0, 27.0],
    })
    result = generate_future_features(last_row, steps=2, weather_df=weather_df)
    assert len(result) == 2
    assert result.iloc[0]["report_date"] == "2024-01-02"
    assert result.iloc[0]["feature_a"] == 5
    assert result.iloc[0]["temp_celsius"] == 26.0
    print("PASS: test_generate_future_features")


def test_arima_model():
    df = pd.DataFrame({
        "report_date": pd.date_range("2024-01-01", periods=50, freq="D"),
        "revenue_target": np.random.randn(50).cumsum() + 100,
    })
    model = ARIMAModel(order=(1, 1, 1))
    model.fit(df, target="revenue_target")
    preds = model.predict(steps=5)
    assert len(preds) == 5
    assert "date" in preds[0]
    assert "predicted_revenue" in preds[0]
    metrics = model.evaluate()
    assert "rmse" in metrics
    assert "mae" in metrics
    print("PASS: test_arima_model")


def test_sarima_model():
    df = pd.DataFrame({
        "report_date": pd.date_range("2024-01-01", periods=60, freq="D"),
        "revenue_target": np.random.randn(60).cumsum() + 100,
        "temp_celsius": 25 + np.random.randn(60) * 3,
    })
    model = SARIMAModel(order=(1, 1, 1), seasonal_order=(1, 1, 1, 7))
    model.fit(df, target="revenue_target", exog_col="temp_celsius")
    preds = model.predict(steps=5)
    assert len(preds) == 5
    metrics = model.evaluate()
    assert "rmse" in metrics
    assert "mae" in metrics
    print("PASS: test_sarima_model")


def test_sarima_model_bounds_training_window_and_fit_options():
    df = pd.DataFrame({
        "report_date": pd.date_range("2024-01-01", periods=80, freq="D"),
        "revenue_target": np.linspace(100, 180, 80),
        "temp_celsius": np.full(80, 25.0),
    })
    model = SARIMAModel(
        order=(1, 1, 1),
        seasonal_order=(1, 1, 1, 7),
        max_training_rows=30,
        fit_options={"method": "lbfgs", "maxiter": 50},
    )

    with patch("statsmodels.tsa.statespace.sarimax.SARIMAX") as sarimax:
        sarimax.return_value.fit.return_value = object()
        model.fit(df, target="revenue_target", exog_col="temp_celsius")

    fitted_args, fitted_kwargs = sarimax.call_args
    assert len(fitted_args[0]) == 30
    assert len(fitted_kwargs["exog"]) == 30
    fit_kwargs = sarimax.return_value.fit.call_args.kwargs
    assert fit_kwargs["method"] == "lbfgs"
    assert fit_kwargs["maxiter"] == 50
    print("PASS: test_sarima_model_bounds_training_window_and_fit_options")


def test_forecaster_sarima_uses_configured_training_window():
    model = Forecaster(config=Config()).get_model("sarima")
    assert model.max_training_rows == 365
    assert model.fit_options["method"] == "lbfgs"
    assert model.fit_options["maxiter"] == 50
    print("PASS: test_forecaster_sarima_uses_configured_training_window")


def test_linear_regression_model():
    df = pd.DataFrame({
        "report_date": pd.date_range("2024-01-01", periods=50, freq="D"),
        "revenue_target": np.random.randn(50).cumsum() + 100,
        "log_revenue": np.log(np.random.randn(50).cumsum() + 200),
        "feature_a": np.random.randn(50),
        "feature_b": np.random.randn(50),
    })
    model = LinearRegressionModel(alpha=1.0, use_log_target=False)
    model.fit(df)
    preds = model.predict(df.tail(5), steps=5)
    assert len(preds) == 5
    future_df = generate_future_features(df.iloc[-1], steps=5)
    future_preds = model.predict(df, steps=5, future_features=future_df)
    assert len(future_preds) == 5
    metrics = model.evaluate()
    assert "rmse" in metrics
    assert "mae" in metrics
    print("PASS: test_linear_regression_model")


def test_forecaster():
    config = Config()
    forecaster = Forecaster(config=config)
    assert forecaster.config is not None
    assert forecaster.weather_manager is not None
    print("PASS: test_forecaster_init")


def test_weather_manager_neutral():
    wm = WeatherManager()
    wm.load_historical = lambda *args, **kwargs: None
    df = wm.get_forecast_weather(date.today(), 7)
    assert len(df) == 7
    assert "date" in df.columns
    assert "temp_celsius" in df.columns
    print("PASS: test_weather_manager_neutral")


def test_weather_manager_no_client():
    wm = WeatherManager(client=None)
    wm.load_historical(days=730)
    df = wm.get_forecast_weather(date.today(), 1)
    assert len(df) == 1
    print("PASS: test_weather_manager_no_client")


def test_forecast_result():
    result = ForecastResult("arima", [{"date": "2024-01-01", "predicted_revenue": 100.0}], metrics={"rmse": 1.0})
    d = result.to_dict()
    assert d["model"] == "arima"
    assert len(d["forecasts"]) == 1
    assert d["error"] is None

    error_result = ForecastResult("arima", [], error="some error")
    assert error_result.to_dict()["error"] == "some error"
    print("PASS: test_forecast_result")


def test_weather_manager_backfill():
    wm = WeatherManager(client=None)
    wm.load_historical = lambda *args, **kwargs: None
    dates = ["2024-01-01", "2024-01-02", "2024-01-03"]
    result = wm.backfill(dates)
    assert len(result) == 3
    for d in dates:
        assert d in result
        assert "temp_celsius" in result[d]
        assert "precipitation_mm" in result[d]
        assert "weather_main" in result[d]
    print("PASS: test_weather_manager_backfill")


def test_weather_manager_sync():
    wm = WeatherManager(client=None)
    wm.load_historical = lambda *args, **kwargs: None
    success = wm.sync_weather_for_date(date(2024, 1, 15))
    assert success is True
    status = wm.get_backfill_status()
    assert "dates_cached" in status
    assert "historical_patterns" in status
    print("PASS: test_weather_manager_sync")


def test_weather_manager_status():
    wm = WeatherManager(client=None)
    wm.load_historical = lambda *args, **kwargs: None
    status = wm.get_backfill_status()
    assert "dates_cached" in status
    cached = wm.get_cached_weather()
    assert isinstance(cached, dict)
    print("PASS: test_weather_manager_status")


def test_forecaster_model_aliases():
    forecaster = Forecaster(config=Config())
    assert forecaster.get_model("amira") is forecaster.get_model("arima")
    assert forecaster.get_model("samira") is forecaster.get_model("sarima")
    print("PASS: test_forecaster_model_aliases")


def test_forecaster_samira_alias_forecasts():
    df = pd.DataFrame({
        "report_date": pd.date_range("2024-01-01", periods=60, freq="D"),
        "revenue_target": np.linspace(100, 160, 60),
        "temp_celsius": np.full(60, 25.0),
    })
    forecaster = Forecaster(config=Config())
    result = forecaster._forecast_single(
        "samira", df, days=3, wm=None, weather_df=None
    )

    assert result.model_name == "samira"
    assert not result.error
    assert len(result.forecasts) == 3
    print("PASS: test_forecaster_samira_alias_forecasts")


def test_forecaster_train_model():
    config = Config()
    client = None
    forecaster = Forecaster(config=config, client=client)
    result = forecaster.train_model("arima", days=7, post_to_api=False)
    assert result is not None
    assert result.model_name == "arima"
    assert not result.error
    print("PASS: test_forecaster_train_model")


def test_forecaster_train_all():
    config = Config()
    client = None
    forecaster = Forecaster(config=config, client=client)
    results = forecaster.train_all(days=7, post_to_api=False)
    assert len(results) == 3
    for name in ["arima", "sarima", "linear_regression"]:
        assert name in results
        assert not results[name].error
        assert len(results[name].forecasts) > 0
    print("PASS: test_forecaster_train_all")


def test_forecaster_uses_client_fallback():
    """When a PMU client is attached, historical data must be loaded via the
    API instead of the (unreachable) direct MySQL connection. This guards the
    Render deployment where the Hostinger MySQL host is not reachable."""
    from datetime import timedelta

    config = Config()

    class FakeClient:
        def get_transactions(self, start, end):
            rows = []
            cur = start
            i = 0
            while cur <= end and i < 60:
                rows.append({"transaction_date": cur.isoformat(), "total_amount": 100.0 + i})
                cur += timedelta(days=1)
                i += 1
            return rows

        def get_weather(self, start, end):
            return []

    fake = FakeClient()
    forecaster = Forecaster(config=config, client=fake)
    db_called = {"hit": False}
    original_load_db = forecaster._load_from_db

    def trap_db(days=None):
        db_called["hit"] = True
        return original_load_db(days)

    forecaster._load_from_db = trap_db

    df = forecaster.get_historical_df()
    assert not db_called["hit"], "DB path was used despite a client being present"
    assert not df.empty
    print("PASS: test_forecaster_uses_client_fallback")


def test_pmuml_client_uses_internal_ml_data_route():
    client = PMUClient(
        base_url="https://example.test",
        service_secret="test-secret",
    )
    calls = []

    def fake_post(path, payload):
        calls.append((path, payload))
        return {
            "data": [
                {
                    "report_date": "2026-01-01",
                    "revenue_target": 100.0,
                    "temp_celsius": 25.0,
                }
            ]
        }

    client._post = fake_post
    rows = client.get_ml_data(date(2026, 1, 1), date(2026, 1, 2))

    assert calls == [
        (
            "/v1/internal/ml-data",
            {"start_date": "2026-01-01", "end_date": "2026-01-02"},
        )
    ]
    assert rows == [
        {
            "report_date": "2026-01-01",
            "revenue_target": 100.0,
            "temp_celsius": 25.0,
        }
    ]
    assert client.headers["X-PMUML-Secret"] == "test-secret"


def test_forecaster_uses_internal_ml_data_client():
    config = Config()
    requested_start = None

    class FakeClient:
        uses_internal_ml_data = True

        def get_ml_data(self, start, end):
            nonlocal requested_start
            requested_start = start
            return [
                {
                    "report_date": start.isoformat(),
                    "revenue_target": 100.0,
                    "temp_celsius": 25.0,
                }
            ]

    fake = FakeClient()
    forecaster = Forecaster(config=config, client=fake)
    db_called = {"hit": False}
    original_load_db = forecaster._load_from_db

    def trap_db(days=None):
        db_called["hit"] = True
        return original_load_db(days)

    forecaster._load_from_db = trap_db
    df = forecaster.get_historical_df(days=2)

    assert not db_called["hit"]
    assert list(df.columns) == ["report_date", "revenue_target", "temp_celsius"]
    assert df.iloc[0]["report_date"] == requested_start.isoformat()


def run_all_tests():
    tests = [
        test_prepare_dataset,
        test_prepare_dataset_no_test,
        test_generate_future_features,
        test_arima_model,
        test_sarima_model,
        test_sarima_model_bounds_training_window_and_fit_options,
        test_forecaster_sarima_uses_configured_training_window,
        test_linear_regression_model,
        test_forecaster,
        test_weather_manager_neutral,
        test_weather_manager_no_client,
        test_forecast_result,
        test_weather_manager_backfill,
        test_weather_manager_sync,
        test_weather_manager_status,
        test_forecaster_train_model,
        test_forecaster_train_all,
        test_forecaster_uses_client_fallback,
        test_pmuml_client_uses_internal_ml_data_route,
        test_forecaster_uses_internal_ml_data_client,
    ]
    passed = 0
    failed = 0
    for test in tests:
        try:
            test()
            passed += 1
        except Exception as e:
            failed += 1
            print(f"FAIL: {test.__name__}: {e}")
            import traceback
            traceback.print_exc()
    print(f"\n{'='*40}")
    print(f"Results: {passed} passed, {failed} failed out of {len(tests)}")
    if failed > 0:
        sys.exit(1)


if __name__ == "__main__":
    run_all_tests()
