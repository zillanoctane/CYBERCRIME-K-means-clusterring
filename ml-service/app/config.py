"""Service configuration loaded from environment variables."""
from functools import lru_cache

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    """Konfigurasi runtime ML service."""

    app_name: str = "SIANCEK ML Service"
    app_version: str = "1.0.0"
    ml_api_key: str = "siancek-dev-key"
    log_level: str = "INFO"

    # Batas keamanan untuk mencegah payload abusif
    max_samples: int = 100_000
    max_features: int = 64
    max_k: int = 20

    # Random state default agar hasil dapat direproduksi (penting untuk jurnal)
    random_state: int = 42

    model_config = SettingsConfigDict(env_file=".env", env_prefix="", case_sensitive=False)


@lru_cache
def get_settings() -> Settings:
    return Settings()
