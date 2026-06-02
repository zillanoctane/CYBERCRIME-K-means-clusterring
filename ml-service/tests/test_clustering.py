"""Smoke tests untuk modul clustering — memastikan algoritma berjalan dan
reproducible. Jalankan dengan: ``pytest -q`` dari direktori ``ml-service``.
"""
from __future__ import annotations

import numpy as np

from app.clustering import elbow_scan, recommend_k, run_kmeans
from app.preprocessing import prepare
from app.schemas import FeatureSpec


def _toy_dataset(n_per_cluster: int = 40, seed: int = 0) -> list[dict]:
    rng = np.random.default_rng(seed)
    centers = np.array([[0, 0], [8, 8], [0, 8]])
    rows = []
    rid = 0
    for cid, c in enumerate(centers):
        for _ in range(n_per_cluster):
            x, y = rng.normal(loc=c, scale=0.6)
            rows.append({"id": rid, "kerugian": float(x), "korban": float(y), "tipe": ["A", "B", "C"][cid]})
            rid += 1
    return rows


def test_run_kmeans_recovers_known_clusters():
    data = _toy_dataset()
    spec = FeatureSpec(numeric=["kerugian", "korban"], categorical=[])
    prepared = prepare(data, spec)
    result = run_kmeans(prepared.X, n_clusters=3, random_state=42)
    assert len(np.unique(result.labels)) == 3
    assert result.silhouette > 0.5  # cluster sintetik sangat terpisah


def test_elbow_scan_returns_monotonic_wcss():
    data = _toy_dataset()
    spec = FeatureSpec(numeric=["kerugian", "korban"], categorical=[])
    prepared = prepare(data, spec)
    points = elbow_scan(prepared.X, k_min=2, k_max=6, random_state=42)
    wcss = [p["wcss"] for p in points]
    assert all(wcss[i] >= wcss[i + 1] for i in range(len(wcss) - 1))  # WCSS turun monoton
    k, reason = recommend_k(points)
    assert 2 <= k <= 6
    assert reason


def test_categorical_features_supported():
    data = _toy_dataset()
    spec = FeatureSpec(numeric=["kerugian"], categorical=["tipe"])
    prepared = prepare(data, spec)
    result = run_kmeans(prepared.X, n_clusters=3, random_state=42)
    assert result.labels.shape == (len(data),)
