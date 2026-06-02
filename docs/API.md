# API Reference — SIANCEK ML Service

Microservice FastAPI mengekspos endpoint berikut. Base URL default: `http://localhost:8001` (di luar Docker) atau `http://ml-service:8000` (dalam jaringan Docker).

Semua endpoint clustering memerlukan header:

```
X-ML-API-Key: <nilai dari ML_API_KEY env>
Content-Type: application/json
```

Dokumentasi interaktif (Swagger UI) tersedia pada `/docs` dan ReDoc pada `/redoc`.

---

## GET `/health`

Healthcheck. Tidak memerlukan autentikasi.

**Response 200**:

```json
{ "status": "ok", "service": "SIANCEK ML Service", "version": "1.0.0" }
```

---

## POST `/api/v1/elbow`

Menghitung WCSS dan metrik validasi untuk rentang K guna memilih jumlah cluster optimal.

**Request body**:

```json
{
  "data": [
    { "id": 1, "estimasi_kerugian": 5000000, "jumlah_korban": 1, "jenis_kejahatan": "Penipuan Online", "provinsi": "DKI Jakarta" },
    { "id": 2, "estimasi_kerugian": 80000000, "jumlah_korban": 1, "jenis_kejahatan": "Pemerasan Siber", "provinsi": "Jawa Barat" }
  ],
  "features": {
    "numeric": ["estimasi_kerugian", "jumlah_korban"],
    "categorical": ["jenis_kejahatan", "provinsi"],
    "scaler": "standard"
  },
  "k_min": 2,
  "k_max": 8
}
```

**Response 200**:

```json
{
  "points": [
    {
      "k": 2,
      "wcss": 1245.32,
      "silhouette": 0.412,
      "davies_bouldin": 1.234,
      "calinski_harabasz": 256.78
    }
    // ... K=3..8
  ],
  "recommended_k": 4,
  "recommendation_reason": "K=4 dipilih berdasarkan agregat Silhouette, Davies-Bouldin, dan Calinski-Harabasz."
}
```

**Error 400**: ketika kolom fitur tidak ada di data atau fitur kosong.

---

## POST `/api/v1/cluster`

Eksekusi K-Means clustering dengan K yang ditentukan.

**Request body**:

```json
{
  "data": [ /* sama seperti elbow */ ],
  "features": { /* sama seperti elbow */ },
  "n_clusters": 4,
  "random_state": 42,
  "record_id_field": "id"
}
```

**Response 200**:

```json
{
  "n_clusters": 4,
  "assignments": [
    { "id": 1, "cluster": 2 },
    { "id": 2, "cluster": 0 }
  ],
  "profiles": [
    {
      "cluster": 0,
      "size": 312,
      "proportion": 0.21,
      "centroid": { "estimasi_kerugian": 87234521.0, "jumlah_korban": 1.4 },
      "dominant_categorical": { "jenis_kejahatan": "Pemerasan Siber", "provinsi": "DKI Jakarta" },
      "summary": "Cluster ini didominasi oleh jenis_kejahatan=Pemerasan Siber; nilai rata-rata tertinggi pada 'estimasi_kerugian' (87234521.00)"
    }
  ],
  "projection": [
    { "id": 1, "cluster": 2, "x": 0.45, "y": -1.21 }
  ],
  "metrics": {
    "silhouette": 0.524,
    "davies_bouldin": 0.812,
    "calinski_harabasz": 1245.7
  },
  "inertia": 2451.83,
  "iterations": 12,
  "feature_importance": {
    "estimasi_kerugian": 0.382,
    "jenis_kejahatan_Pemerasan Siber": 0.214
  },
  "centroids": [
    { "estimasi_kerugian": 1.23, "jumlah_korban": -0.45 /* dalam ruang ter-scale */ }
  ],
  "random_state": 42
}
```

---

## Error Format (umum)

```json
{ "detail": "Pesan kesalahan" }
```

Kode HTTP yang lazim:

- `400` — input invalid (fitur tidak ada, encoding gagal)
- `401` — `X-ML-API-Key` salah/tidak ada
- `413` — dataset melebihi `max_samples` (default 100.000)
- `422` — validasi Pydantic gagal
- `500` — error tak terduga di server (sudah dicatat di log)

---

## Batas Operasi

Default (dapat diubah di `ml-service/app/config.py`):

| Parameter      | Nilai     | Keterangan                       |
| -------------- | --------- | -------------------------------- |
| `max_samples`  | 100.000   | Jumlah baris maksimal per request |
| `max_features` | 64        | Setelah ekspansi One-Hot          |
| `max_k`        | 20        | Batas atas K                      |

---

## Konsumsi dari Laravel

Dilakukan via `App\Services\MLServiceClient` (lihat `app/Services/MLServiceClient.php`). Singleton di-bind oleh `AppServiceProvider`. Contoh:

```php
$client = app(MLServiceClient::class);
$result = $client->cluster($data, [
    'numeric' => ['estimasi_kerugian', 'jumlah_korban'],
    'categorical' => ['jenis_kejahatan', 'provinsi'],
    'scaler' => 'standard',
], nClusters: 4);
```

Retry sederhana (2 kali) dan _timeout_ 120 detik sudah disertakan; konfigurasi via `.env`.
