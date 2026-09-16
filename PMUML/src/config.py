import os
from typing import Any, Optional
import yaml


class Config:
    _instance: Optional["Config"] = None

    def __new__(cls, *args, **kwargs):
        if cls._instance is None:
            cls._instance = super().__new__(cls)
        return cls._instance

    def __init__(self, config_path: str = "configs/models.yaml"):
        if hasattr(self, "_initialized"):
            return
        self._initialized = True
        self.config_path = config_path
        self._model_config: dict = {}
        self._load_config()

    def _load_config(self):
        try:
            with open(self.config_path, "r") as f:
                self._model_config = yaml.safe_load(f) or {}
        except Exception:
            self._model_config = {}

    @property
    def models(self) -> dict:
        return self._model_config

    def get_model_config(self, model_name: str) -> dict:
        return self._model_config.get(model_name, {})

    @property
    def pmu_api_url(self) -> str:
        return os.getenv("PMU_API_URL", "https://pmu-pasacao.com/api")

    @property
    def pmu_api_token(self) -> str:
        return os.getenv("PMU_API_TOKEN", "")

    @property
    def pmuml_service_secret(self) -> str:
        return os.getenv("PMUML_SERVICE_SECRET", "")

    @property
    def model_version(self) -> str:
        return os.getenv("MODEL_VERSION", "arima-v1")

    @property
    def hostinger_db_host(self) -> str:
        return os.getenv("HOSTINGER_DB_HOST", "srv1041.hstgr.io")

    @property
    def hostinger_db_user(self) -> str:
        return os.getenv("HOSTINGER_DB_USER", "u462546534_pmu")

    @property
    def hostinger_db_pass(self) -> str:
        return os.getenv("HOSTINGER_DB_PASS", "PMUDB2026pw")

    @property
    def hostinger_db_name(self) -> str:
        return os.getenv("HOSTINGER_DB_NAME", "u462546534_pmu_db")

    @property
    def hostinger_db_port(self) -> str:
        return os.getenv("HOSTINGER_DB_PORT", "3306")

    @property
    def port(self) -> int:
        return int(os.getenv("PORT", 5000))

    @property
    def forecast_days(self) -> int:
        return int(os.getenv("FORECAST_DAYS", "30"))

    @property
    def db_url(self) -> str:
        return (
            f"mysql+pymysql://{self.hostinger_db_user}:{self.hostinger_db_pass}"
            f"@{self.hostinger_db_host}:{self.hostinger_db_port}/{self.hostinger_db_name}"
        )
