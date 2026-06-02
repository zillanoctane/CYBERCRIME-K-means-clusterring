# Arsitektur Sistem — SIANCEK

## 1. Gambaran Umum

SIANCEK mengadopsi arsitektur **microservice dua-tier** untuk memisahkan tanggung jawab presentasi/aplikasi dari komputasi _machine learning_:

```
┌──────────────────────────────────────────────────────────────────┐
│                       Pengguna (Web Browser)                      │
└──────────────────────────────────────────────────────────────────┘
                              │ HTTPS
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│  Nginx (reverse proxy)          ←→        Laravel 11 (PHP-FPM)   │
│                                            ───────────────────    │
│                                            • Auth + RBAC          │
│                                            • CRUD Cybercrime      │
│                                            • Service Layer        │
│                                            • Blade + Tailwind     │
└──────────────────────────────────────────────────────────────────┘
              │ Eloquent ORM                  │ HTTP (REST)
              ▼                                ▼
   ┌───────────────────────┐        ┌──────────────────────────┐
   │  MySQL 8.0             │        │  FastAPI ML Service       │
   │  ────────────────       │        │  ────────────────────     │
   │  cybercrime_records    │        │  /api/v1/elbow            │
   │  clustering_runs       │        │  /api/v1/cluster          │
   │  cluster_assignments   │        │  scikit-learn pipeline    │
   │  users, activity_logs  │        │  PCA visualization        │
   └───────────────────────┘        └──────────────────────────┘
                                              │
                                              ▼
                                     ┌──────────────────┐
                                     │  Redis (cache)    │
                                     └──────────────────┘
```

## 2. Alasan Pemilihan Arsitektur

| Aspek                       | Justifikasi                                                                  |
| --------------------------- | ---------------------------------------------------------------------------- |
| Pisahkan ML & Web           | Bahasa & ekosistem berbeda; scikit-learn matang di Python                    |
| REST API kontrak jelas      | Memudahkan pengujian, debugging, swap teknologi di masa depan                |
| Stateless ML service        | Dapat di-_scale horizontal_ tanpa state management                           |
| Authentication shared-secret| Cocok untuk komunikasi internal (intra-cluster); ringan dan cepat            |
| Docker Compose orchestration| Reproducible environment untuk skripsi/dosen penguji                        |

## 3. Diagram Komponen

```
┌─ laravel-app ──────────────────────────────────────────────┐
│  Controllers ─────────┐                                    │
│                       ▼                                    │
│             Services (business)                            │
│             ├── ClusteringService ──── HTTP ─┐             │
│             ├── MLServiceClient              │             │
│             └── ReportService                │             │
│                       │                       │             │
│              Eloquent Models                  │             │
│                       │                       │             │
│              ┌────────▼──────┐                │             │
│              │   MySQL       │                │             │
│              └───────────────┘                │             │
└────────────────────────────────────────────────┼────────────┘
                                                 │
                                                 ▼
┌─ ml-service ────────────────────────────────────────────────┐
│  FastAPI router ──► handlers ──► preprocessing ──► KMeans  │
│                                       │             │       │
│                                       └─► viz (PCA) ◄──────┘│
└─────────────────────────────────────────────────────────────┘
```

## 4. Alur Sekuens — Menjalankan Clustering

```
User                Web (Laravel)         ML Service          DB
 │   submit form     │                    │                    │
 ├──────────────────►│                    │                    │
 │                   │  buildDataset(...) │                    │
 │                   ├───────────────────────────────────────► │
 │                   │  ◄─────────────────────────────────── rows
 │                   │  POST /api/v1/cluster                  │
 │                   ├───────────────────►│                    │
 │                   │                    │ preprocess         │
 │                   │                    │ KMeans.fit_predict │
 │                   │                    │ metrics + PCA      │
 │                   │  ◄─────────────────│ JSON               │
 │                   │  insert run        │                    │
 │                   ├───────────────────────────────────────► │
 │                   │  insert assignments│                    │
 │                   ├───────────────────────────────────────► │
 │  redirect show    │                    │                    │
 │◄──────────────────┤                    │                    │
```

## 5. Skema Database (ringkas)

```
users
 ├─ id, name, email, password, role, instansi, is_active, last_login_at

cybercrime_records              (1.500+ baris sintetik default)
 ├─ id, nomor_laporan (uniq)
 ├─ tanggal_kejadian, tanggal_laporan
 ├─ jenis_kejahatan, sub_jenis, modus_operandi, platform
 ├─ provinsi, kota_kabupaten, lat, lng
 ├─ usia_korban, jenis_kelamin_korban, pekerjaan_korban, pendidikan_korban
 ├─ estimasi_kerugian, jumlah_korban, tingkat_keparahan
 └─ status_kasus, sumber_data, keterangan, input_by(FK→users)

clustering_runs
 ├─ id, nama, deskripsi, n_clusters, fitur_numerik(JSON), fitur_kategorikal(JSON)
 ├─ scaler, filter(JSON), random_state, jumlah_data
 ├─ inertia, silhouette, davies_bouldin, calinski_harabasz, iterations
 ├─ hasil_json(JSON), elbow_points(JSON)
 ├─ mode(manual|auto), status(draft|sukses|gagal), error_message
 └─ created_by(FK→users)

cluster_assignments
 ├─ clustering_run_id(FK), cybercrime_record_id(FK)
 ├─ cluster, pca_x, pca_y
 └─ UNIQUE(run_id, record_id)

activity_logs
 ├─ user_id, aksi, subjek_type, subjek_id, metadata(JSON), ip, ua
```

## 6. Keamanan

| Lapisan              | Mekanisme                                                            |
| -------------------- | -------------------------------------------------------------------- |
| Autentikasi          | Laravel session + remember-me; password di-hash dengan bcrypt        |
| Otorisasi            | `EnsureUserHasRole` middleware; tiga peran (Admin/Analis/Viewer)     |
| CSRF                 | Default Laravel pada semua form                                      |
| Injeksi SQL          | Eloquent (parameterized); whitelist fitur di service layer            |
| Komunikasi ML        | Shared secret `X-ML-API-Key` (untuk produksi: mTLS / VPN internal)   |
| Data sensitif        | Anonimisasi sebelum import; soft-delete pada records                 |
| Audit trail          | `activity_logs` mencatat tiap aksi mutatif                          |

## 7. Performa & Skalabilitas

- **Indeks database**: pada `tanggal_kejadian`, `jenis_kejahatan`, `provinsi`, `status` (lihat migrations).
- **Bulk insert**: penugasan cluster di-insert dalam _chunk_ 500 baris (`ClusteringService::runAndPersist`).
- **Stateless ML service**: dapat di-replikasi di belakang load balancer; cache hasil populer di Redis.
- **Pagination**: semua daftar data tabular menggunakan paginator 15–20 baris.

## 8. Deployment

```yaml
services:
  nginx ── ports 8000:80
  app   ── PHP-FPM 8.3
  mysql ── 8.0 (persisted volume)
  ml-service ── FastAPI Uvicorn 2 workers, ports 8001:8000
  redis ── 7-alpine (opsional, untuk cache)
```

Container saling terhubung melalui jaringan Docker bawaan `siancek-net`. Tidak ada port database/ML yang perlu di-_expose_ secara publik dalam mode produksi.

## 9. Extensibility (Pengembangan Lanjutan)

- **Algoritma alternatif** — `MLServiceClient` mudah diperluas untuk endpoint baru (e.g., `/api/v1/dbscan`, `/api/v1/agglomerative`).
- **Real-time ingestion** — tambah job queue Laravel + endpoint webhook untuk integrasi dengan sistem laporan eksternal.
- **Geo-spatial heatmap** — view `analysis/show.blade.php` dapat dilengkapi dengan Leaflet menggunakan kolom `latitude/longitude`.
- **Time-series forecasting** — ekstensi ML service untuk prediksi tren per cluster.
