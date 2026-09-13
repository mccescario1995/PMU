import os
import sys
from dotenv import load_dotenv

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))
load_dotenv(os.path.join(os.path.dirname(__file__), "..", ".env"))

from src.features import prepare_dataset, generate_future_features
from src.models.amira import AMIRAModel
from src.models.samira import SAMIRAModel
from src.models.linear_regression import LinearRegressionModel
from src.pmu_client import PMUClient
from src.forecaster import Forecaster, ForecastResult, Config
from src.weather_service import WeatherManager

import pandas as pd
import numpy as np
from datetime import date


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


def test_amira_model():
    df = pd.DataFrame({
        "report_date": pd.date_range("2024-01-01", periods=50, freq="D"),
        "revenue_target": np.random.randn(50).cumsum() + 100,
    })
    model = AMIRAModel(order=(1, 1, 1))
    model.fit(df, target="revenue_target")
    preds = model.predict(steps=5)
    assert len(preds) == 5
    assert "date" in preds[0]
    assert "predicted_revenue" in preds[0]
    metrics = model.evaluate()
    assert "rmse" in metrics
    assert "mae" in metrics
    print("PASS: test_amira_model")


def test_samira_model():
    df = pd.DataFrame({
        "report_date": pd.date_range("2024-01-01", periods=60, freq="D"),
        "revenue_target": np.random.randn(60).cumsum() + 100,
        "temp_celsius": 25 + np.random.randn(60) * 3,
    })
    model = SAMIRAModel(order=(1, 1, 1), seasonal_order=(1, 1, 1, 7))
    model.fit(df, target="revenue_target", exog_col="temp_celsius")
    preds = model.predict(steps=5)
    assert len(preds) == 5
    metrics = model.evaluate()
    assert "rmse" in metrics
    assert "mae" in metrics
    print("PASS: test_samira_model")


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
    result = ForecastResult("amira", [{"date": "2024-01-01", "predicted_revenue": 100.0}], metrics={"rmse": 1.0})
    d = result.to_dict()
    assert d["model"] == "amira"
    assert len(d["forecasts"]) == 1
    assert d["error"] is None

    error_result = ForecastResult("amira", [], error="some error")
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


def test_forecaster_train_model():
    config = Config()
    client = None
    forecaster = Forecaster(config=config, client=client)
    result = forecaster.train_model("amira", days=7, post_to_api=False)
    assert result is not None
    assert result.model_name == "amira"
    assert not result.error
    print("PASS: test_forecaster_train_model")


def test_forecaster_train_all():
    config = Config()
    client = None
    forecaster = Forecaster(config=config, client=client)
    results = forecaster.train_all(days=7, post_to_api=False)
    assert len(results) == 3
    for name in ["amira", "samira", "linear_regression"]:
        assert name in results
        assert not results[name].error
        assert len(results[name].forecasts) > 0
    print("PASS: test_forecaster_train_all")


def run_all_tests():
    tests = [
        test_prepare_dataset,
        test_prepare_dataset_no_test,
        test_generate_future_features,
        test_amira_model,
        test_samira_model,
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
