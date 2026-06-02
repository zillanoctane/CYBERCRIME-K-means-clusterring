"""Implementasi K-Means clustering, validasi internal, dan profil cluster.

Modul ini fokus pada algoritma — preprocessing dipisah ke ``preprocessing.py``
dan visualisasi (PCA 2D) ke ``visualization.py``. Pembagian ini mengikuti
prinsip *separation of concerns* sehingga setiap modul mudah diuji.

Algoritma utama:
    - KMeans dengan inisialisasi ``k-means++`` (Arthur & Vassilvitskii, 2007)
    - Validasi internal: Silhouette (Rousseeuw, 1987), Davies–Bouldin (Davies &
      Bouldin, 1979), Calinski–Harabasz (Caliński & Harabasz, 1974)
    - Rekomendasi K otomatis berbasis *Kneedle algorithm* sederhana untuk WCSS,
      dikonfirmasi dengan rangking Silhouette dan Davies–Bouldin.
"""
from __future__ import annotations

from dataclasses import dataclass

import numpy as np
import pandas as pd
from sklearn.cluster import KMeans
from sklearn.metrics import (
    calinski_harabasz_score,
    davies_bouldin_score,
    silhouette_score,
)

from .preprocessing import PreparedData


@dataclass
class ClusteringResult:
    labels: np.ndarray
    inertia: float
    n_iter: int
    centroids_scaled: np.ndarray
    silhouette: float
    davies_bouldin: float
    calinski_harabasz: float


def run_kmeans(X: np.ndarray, n_clusters: int, random_state: int = 42) -> ClusteringResult:
    """Jalankan K-Means dan hitung metrik validasi internal."""
    if X.shape[0] < n_clusters:
        raise ValueError(f"Jumlah sampel ({X.shape[0]}) lebih kecil dari n_clusters ({n_clusters})")

    model = KMeans(
        n_clusters=n_clusters,
        init="k-means++",
        n_init=10,
        max_iter=300,
        tol=1e-4,
        random_state=random_state,
        algorithm="lloyd",
    )
    labels = model.fit_predict(X)

    # Silhouette tidak bisa dihitung untuk k=1; FastAPI sudah memvalidasi >=2
    silhouette = float(silhouette_score(X, labels)) if n_clusters > 1 else 0.0
    db = float(davies_bouldin_score(X, labels)) if n_clusters > 1 else 0.0
    ch = float(calinski_harabasz_score(X, labels)) if n_clusters > 1 else 0.0

    return ClusteringResult(
        labels=labels,
        inertia=float(model.inertia_),
        n_iter=int(model.n_iter_),
        centroids_scaled=model.cluster_centers_,
        silhouette=silhouette,
        davies_bouldin=db,
        calinski_harabasz=ch,
    )


def elbow_scan(X: np.ndarray, k_min: int, k_max: int, random_state: int = 42) -> list[dict]:
    """Hitung WCSS dan metrik validasi untuk rentang K (Elbow Method)."""
    points: list[dict] = []
    for k in range(k_min, k_max + 1):
        result = run_kmeans(X, n_clusters=k, random_state=random_state)
        points.append(
            {
                "k": k,
                "wcss": result.inertia,
                "silhouette": result.silhouette,
                "davies_bouldin": result.davies_bouldin,
                "calinski_harabasz": result.calinski_harabasz,
            }
        )
    return points


def recommend_k(points: list[dict]) -> tuple[int, str]:
    """Rekomendasikan K terbaik berdasarkan kombinasi 3 metrik.

    Strategi:
        1. Hitung "elbow score" sebagai pengurangan WCSS yang diturunkan
           (second difference) — proksi sederhana untuk kneedle algorithm.
        2. Rangking K berdasarkan silhouette (desc) dan davies_bouldin (asc).
        3. Pilih K dengan total rank terbaik (minimum sum of ranks).
    """
    if not points:
        raise ValueError("Tidak ada titik elbow untuk dievaluasi")

    df = pd.DataFrame(points).sort_values("k").reset_index(drop=True)

    # Elbow score via second difference WCSS (semakin besar = patahan semakin tajam)
    wcss = df["wcss"].to_numpy()
    if len(wcss) >= 3:
        d2 = np.diff(wcss, n=2)
        # second-diff index i berhubungan dengan k = df.k[i+1]
        elbow_idx = int(np.argmax(d2)) + 1
        elbow_k = int(df["k"].iloc[elbow_idx])
    else:
        elbow_k = int(df["k"].iloc[0])

    # Rank-based composite (semakin kecil rank semakin baik)
    df["rank_sil"] = df["silhouette"].rank(ascending=False, method="min")
    df["rank_db"] = df["davies_bouldin"].rank(ascending=True, method="min")
    df["rank_ch"] = df["calinski_harabasz"].rank(ascending=False, method="min")
    df["total_rank"] = df["rank_sil"] + df["rank_db"] + df["rank_ch"]

    best_idx = int(df["total_rank"].idxmin())
    composite_k = int(df["k"].iloc[best_idx])

    # Jika elbow dan komposit setuju → keyakinan tinggi
    if elbow_k == composite_k:
        return composite_k, (
            f"K={composite_k} direkomendasikan: titik siku (elbow) dan agregat "
            f"silhouette/Davies-Bouldin/Calinski-Harabasz konsisten menunjuk nilai yang sama."
        )

    # Kalau berbeda, prioritaskan komposit (lebih robust) tetapi sebutkan elbow
    return composite_k, (
        f"K={composite_k} dipilih berdasarkan agregat Silhouette, Davies-Bouldin, dan "
        f"Calinski-Harabasz. Sebagai pembanding, titik siku WCSS berada di K={elbow_k}."
    )


def profile_clusters(prepared: PreparedData, labels: np.ndarray) -> list[dict]:
    """Bangun ringkasan deskriptif tiap cluster dalam ruang fitur ASLI (sebelum scaling)."""
    df = prepared.df.copy()
    df["__cluster__"] = labels
    total = len(df)

    numeric_cols = df.select_dtypes(include=["number"]).columns.tolist()
    if "__cluster__" in numeric_cols:
        numeric_cols.remove("__cluster__")
    categorical_cols = df.select_dtypes(exclude=["number"]).columns.tolist()

    profiles: list[dict] = []
    for cluster_id, group in df.groupby("__cluster__"):
        centroid_real = {col: float(group[col].mean()) for col in numeric_cols if not group[col].isna().all()}
        dominant_cat: dict[str, str] = {}
        for col in categorical_cols:
            if group[col].isna().all():
                continue
            mode = group[col].mode(dropna=True)
            if not mode.empty:
                dominant_cat[col] = str(mode.iloc[0])

        # Narasi singkat — membantu interpretasi di laporan/jurnal
        summary_parts = []
        if dominant_cat:
            top_cat = ", ".join(f"{k}={v}" for k, v in list(dominant_cat.items())[:3])
            summary_parts.append(f"didominasi oleh {top_cat}")
        if centroid_real:
            top_num = max(centroid_real.items(), key=lambda kv: abs(kv[1]))
            summary_parts.append(f"nilai rata-rata tertinggi pada '{top_num[0]}' ({top_num[1]:.2f})")

        summary = "Cluster ini " + "; ".join(summary_parts) if summary_parts else "Profil cluster tidak tersedia."

        profiles.append(
            {
                "cluster": int(cluster_id),
                "size": int(len(group)),
                "proportion": float(len(group) / total),
                "centroid": centroid_real,
                "dominant_categorical": dominant_cat,
                "summary": summary,
            }
        )
    profiles.sort(key=lambda p: p["cluster"])
    return profiles


def feature_importance(prepared: PreparedData, labels: np.ndarray) -> dict[str, float]:
    """Importance kasar berdasarkan variansi rata-rata fitur antar cluster.

    Fitur yang nilai rata-ratanya jauh berbeda antar cluster lebih informatif.
    Output dinormalisasi (sum=1) sehingga dapat ditampilkan sebagai bar chart.
    """
    X = prepared.X
    if X.shape[1] == 0:
        return {}

    df_scaled = pd.DataFrame(X, columns=prepared.feature_names)
    df_scaled["__cluster__"] = labels
    means = df_scaled.groupby("__cluster__").mean()
    var_between = means.var(axis=0)
    total = float(var_between.sum())
    if total <= 0:
        return {name: 0.0 for name in prepared.feature_names}
    return {name: float(v / total) for name, v in var_between.items()}
