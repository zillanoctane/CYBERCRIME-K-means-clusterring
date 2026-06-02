"""FastAPI entrypoint untuk SIANCEK ML Service.

Service ini dikonsumsi oleh aplikasi Laravel via HTTP. Setiap endpoint mengikuti
kontrak Pydantic di ``schemas.py`` sehingga klien Laravel dapat menggunakan
typed DTO. Autentikasi sederhana via header ``X-ML-API-Key``.
"""
from __future__ import annotations

import logging
from typing import Annotated

import numpy as np
import pandas as pd
from fastapi import Depends, FastAPI, Header, HTTPException, status
from fastapi.middleware.cors import CORSMiddleware

from .clustering import elbow_scan, feature_importance, profile_clusters, recommend_k, run_kmeans
from .config import Settings, get_settings
from .preprocessing import prepare
from .schemas import (
    ClusterRequest,
    ClusterResponse,
    ElbowRequest,
    ElbowResponse,
    HealthResponse,
)
from .visualization import project_2d

logger = logging.getLogger("siancek.ml")

app = FastAPI(
    title="SIANCEK ML Service",
    description=(
        "Microservice machine learning untuk Sistem Analisis Klasterisasi Cybercrime. "
        "Menyediakan operasi K-Means clustering, validasi internal (Elbow, Silhouette, "
        "Davies-Bouldin, Calinski-Harabasz), dan proyeksi PCA untuk visualisasi."
    ),
    version="1.0.0",
    contact={"name": "Tim SIANCEK", "email": "dev@siancek.local"},
    license_info={"name": "Akademik (penelitian)"},
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["GET", "POST"],
    allow_headers=["*"],
)


def require_api_key(
    settings: Annotated[Settings, Depends(get_settings)],
    x_ml_api_key: Annotated[str | None, Header(alias="X-ML-API-Key")] = None,
) -> None:
    """Validasi sederhana berbasis shared secret."""
    if not x_ml_api_key or x_ml_api_key != settings.ml_api_key:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Header X-ML-API-Key tidak valid",
        )


@app.get("/health", response_model=HealthResponse, tags=["meta"])
def health() -> HealthResponse:
    """Healthcheck — dipanggil docker-compose & Laravel."""
    settings = get_settings()
    return HealthResponse(service=settings.app_name, version=settings.app_version)


@app.post(
    "/api/v1/elbow",
    response_model=ElbowResponse,
    tags=["clustering"],
    dependencies=[Depends(require_api_key)],
    summary="Hitung kurva Elbow dan rekomendasi K optimal",
)
def elbow(req: ElbowRequest) -> ElbowResponse:
    settings = get_settings()
    _validate_dataset_size(req.data, settings)
    if req.k_max > settings.max_k:
        raise HTTPException(400, f"k_max melebihi batas {settings.max_k}")

    try:
        prepared = prepare(req.data, req.features)
    except ValueError as exc:
        raise HTTPException(400, str(exc)) from exc

    points = elbow_scan(prepared.X, req.k_min, req.k_max, random_state=settings.random_state)
    best_k, reason = recommend_k(points)
    return ElbowResponse(points=points, recommended_k=best_k, recommendation_reason=reason)


@app.post(
    "/api/v1/cluster",
    response_model=ClusterResponse,
    tags=["clustering"],
    dependencies=[Depends(require_api_key)],
    summary="Eksekusi K-Means clustering",
)
def cluster(req: ClusterRequest) -> ClusterResponse:
    settings = get_settings()
    _validate_dataset_size(req.data, settings)

    try:
        prepared = prepare(req.data, req.features)
    except ValueError as exc:
        raise HTTPException(400, str(exc)) from exc

    random_state = req.random_state if req.random_state is not None else settings.random_state
    result = run_kmeans(prepared.X, n_clusters=req.n_clusters, random_state=random_state)

    # Susun penugasan record -> cluster
    id_field = req.record_id_field
    ids = prepared.df[id_field].tolist() if id_field in prepared.df.columns else list(range(len(prepared.df)))
    assignments = [{"id": ids[i], "cluster": int(result.labels[i])} for i in range(len(ids))]

    # Profil cluster dihitung di ruang fitur asli (lebih interpretable)
    profiles = profile_clusters(prepared, result.labels)

    # PCA 2D untuk scatter plot
    projection = project_2d(prepared.X, result.labels, ids=ids)

    # Centroid dalam ruang fitur ter-scale (debug / lanjutan)
    centroids = [
        {prepared.feature_names[j]: float(result.centroids_scaled[c, j]) for j in range(len(prepared.feature_names))}
        for c in range(req.n_clusters)
    ]

    importance = feature_importance(prepared, result.labels)

    return ClusterResponse(
        n_clusters=req.n_clusters,
        assignments=assignments,
        profiles=profiles,
        projection=projection,
        metrics={
            "silhouette": result.silhouette,
            "davies_bouldin": result.davies_bouldin,
            "calinski_harabasz": result.calinski_harabasz,
        },
        inertia=result.inertia,
        iterations=result.n_iter,
        feature_importance=importance,
        centroids=centroids,
        random_state=random_state,
    )


def _validate_dataset_size(data: list, settings: Settings) -> None:
    if len(data) > settings.max_samples:
        raise HTTPException(413, f"Dataset terlalu besar (>{settings.max_samples} baris)")


@app.exception_handler(Exception)
async def unhandled_exception_handler(request, exc):  # type: ignore[no-untyped-def]
    """Tangkap exception tak terduga agar response tetap konsisten JSON."""
    logger.exception("Unhandled error pada %s: %s", request.url.path, exc)
    return HTTPException(
        status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
        detail=f"Internal error: {exc.__class__.__name__}",
    ).to_dict() if hasattr(HTTPException, "to_dict") else {"detail": "internal error"}


__all__ = ["app"]


def _to_jsonable(value):  # pragma: no cover - utility, dipakai bila dibutuhkan
    """Konversi tipe numpy/pandas yang tidak JSON-serializable secara default."""
    if isinstance(value, (np.integer,)):
        return int(value)
    if isinstance(value, (np.floating,)):
        return float(value)
    if isinstance(value, np.ndarray):
        return value.tolist()
    if isinstance(value, pd.Series):
        return value.tolist()
    return value
