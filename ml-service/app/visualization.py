"""Reduksi dimensi untuk visualisasi cluster pada bidang 2D (PCA)."""
from __future__ import annotations

from typing import Any

import numpy as np
from sklearn.decomposition import PCA


def project_2d(X: np.ndarray, labels: np.ndarray, ids: list[Any] | None = None) -> list[dict[str, Any]]:
    """Proyeksikan ruang fitur ke 2 komponen utama untuk scatter plot."""
    if X.shape[1] < 2:
        # Tambahkan komponen nol agar plot tetap bisa dirender
        padding = np.zeros((X.shape[0], 2 - X.shape[1]))
        X = np.hstack([X, padding])

    pca = PCA(n_components=2, random_state=42)
    coords = pca.fit_transform(X)

    n = len(coords)
    if ids is None or len(ids) != n:
        ids = list(range(n))

    return [
        {
            "id": ids[i],
            "cluster": int(labels[i]),
            "x": float(coords[i, 0]),
            "y": float(coords[i, 1]),
        }
        for i in range(n)
    ]
