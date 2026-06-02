"""Tahap preparation data: encoding, scaling, dan validasi fitur.

Mengikuti tahap *Data Preparation* dalam kerangka CRISP-DM. Output utama adalah
matriks fitur ``X`` (numpy array) yang siap dipakai untuk K-Means.
"""
from __future__ import annotations

from dataclasses import dataclass

import numpy as np
import pandas as pd
from sklearn.compose import ColumnTransformer
from sklearn.impute import SimpleImputer
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import (
    MinMaxScaler,
    OneHotEncoder,
    RobustScaler,
    StandardScaler,
)

from .schemas import FeatureSpec

_SCALERS = {
    "standard": StandardScaler,
    "minmax": MinMaxScaler,
    "robust": RobustScaler,
}


@dataclass
class PreparedData:
    """Hasil tahap preprocessing.

    Atribut:
        X: matriks fitur (n_samples × n_features) setelah scaling/encoding
        df: DataFrame asli dengan kolom ``id`` (jika ada)
        feature_names: nama fitur setelah ekspansi One-Hot
        pipeline: scikit-learn ``ColumnTransformer`` yang dilatih (untuk re-use)
    """

    X: np.ndarray
    df: pd.DataFrame
    feature_names: list[str]
    pipeline: ColumnTransformer


def _build_pipeline(spec: FeatureSpec) -> ColumnTransformer:
    """Bangun ColumnTransformer berdasarkan spesifikasi fitur."""
    scaler_cls = _SCALERS[spec.scaler]

    numeric_pipe = Pipeline(
        steps=[
            ("imputer", SimpleImputer(strategy="median")),
            ("scaler", scaler_cls()),
        ]
    )
    categorical_pipe = Pipeline(
        steps=[
            ("imputer", SimpleImputer(strategy="most_frequent")),
            ("encoder", OneHotEncoder(handle_unknown="ignore", sparse_output=False)),
        ]
    )

    transformers = []
    if spec.numeric:
        transformers.append(("num", numeric_pipe, spec.numeric))
    if spec.categorical:
        transformers.append(("cat", categorical_pipe, spec.categorical))

    if not transformers:
        raise ValueError("Minimal satu fitur (numerik atau kategorikal) harus diberikan")

    return ColumnTransformer(transformers=transformers, remainder="drop", verbose_feature_names_out=False)


def prepare(records: list[dict], spec: FeatureSpec) -> PreparedData:
    """Bersihkan, encode, dan scale data sebelum clustering."""
    df = pd.DataFrame.from_records(records)

    missing = [c for c in (spec.numeric + spec.categorical) if c not in df.columns]
    if missing:
        raise ValueError(f"Kolom fitur tidak ditemukan dalam data: {missing}")

    # Pastikan kolom numerik bertipe numeric (coerce string -> NaN)
    for col in spec.numeric:
        df[col] = pd.to_numeric(df[col], errors="coerce")

    # Pastikan kolom kategorikal berupa string (None tetap None untuk imputer)
    for col in spec.categorical:
        df[col] = df[col].astype("object").where(df[col].notna(), None)

    pipeline = _build_pipeline(spec)
    X = pipeline.fit_transform(df)
    feature_names = list(pipeline.get_feature_names_out())

    return PreparedData(X=np.asarray(X, dtype=np.float64), df=df, feature_names=feature_names, pipeline=pipeline)
