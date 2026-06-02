"""Pydantic schemas — kontrak antara Laravel app dan ML service."""
from __future__ import annotations

from typing import Any, Literal

from pydantic import BaseModel, ConfigDict, Field, field_validator


class CybercrimeRecord(BaseModel):
    """Satu baris data laporan cybercrime.

    Field bebas (Dict[str, Any]) agar fleksibel: Laravel mengirim baris hasil query
    dengan kolom apa pun, asal sesuai dengan ``features`` yang diminta.
    """

    model_config = ConfigDict(extra="allow")

    id: int | None = None


class FeatureSpec(BaseModel):
    """Spesifikasi fitur untuk preprocessing."""

    numeric: list[str] = Field(default_factory=list, description="Kolom numerik yang ikut clustering")
    categorical: list[str] = Field(default_factory=list, description="Kolom kategorikal — akan one-hot encoded")
    scaler: Literal["standard", "minmax", "robust"] = "standard"

    @field_validator("numeric", "categorical")
    @classmethod
    def _no_duplicates(cls, v: list[str]) -> list[str]:
        if len(v) != len(set(v)):
            raise ValueError("Daftar fitur mengandung duplikasi")
        return v


class ElbowRequest(BaseModel):
    data: list[dict[str, Any]] = Field(..., min_length=10)
    features: FeatureSpec
    k_min: int = Field(2, ge=2, le=20)
    k_max: int = Field(10, ge=2, le=20)

    @field_validator("k_max")
    @classmethod
    def _validate_k_range(cls, v: int, info) -> int:  # type: ignore[no-untyped-def]
        if "k_min" in info.data and v < info.data["k_min"]:
            raise ValueError("k_max harus >= k_min")
        return v


class ElbowPoint(BaseModel):
    k: int
    wcss: float
    silhouette: float
    davies_bouldin: float
    calinski_harabasz: float


class ElbowResponse(BaseModel):
    points: list[ElbowPoint]
    recommended_k: int
    recommendation_reason: str


class ClusterRequest(BaseModel):
    data: list[dict[str, Any]] = Field(..., min_length=10)
    features: FeatureSpec
    n_clusters: int = Field(..., ge=2, le=20)
    random_state: int | None = None
    record_id_field: str = "id"


class ClusterProfile(BaseModel):
    cluster: int
    size: int
    proportion: float
    centroid: dict[str, float]
    dominant_categorical: dict[str, str]
    summary: str


class ProjectionPoint(BaseModel):
    id: int | str | None
    cluster: int
    x: float
    y: float


class ClusterResponse(BaseModel):
    n_clusters: int
    assignments: list[dict[str, Any]]
    profiles: list[ClusterProfile]
    projection: list[ProjectionPoint]
    metrics: dict[str, float]
    inertia: float
    iterations: int
    feature_importance: dict[str, float]
    centroids: list[dict[str, float]]
    random_state: int


class PredictRequest(BaseModel):
    model_id: str
    data: list[dict[str, Any]]


class PredictResponse(BaseModel):
    predictions: list[int]


class HealthResponse(BaseModel):
    status: Literal["ok"] = "ok"
    service: str
    version: str
