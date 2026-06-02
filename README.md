# SIANCEK — Sistem Analisis Klasterisasi Cybercrime

**Aplikasi Analisis Pengelompokan Karakteristik dan Tren Cybercrime menggunakan Algoritma K-Means Clustering**

> Sistem informasi berbasis web untuk menganalisis pola dan karakteristik tindak kejahatan siber (cybercrime) di Indonesia menggunakan pendekatan _unsupervised learning_ dengan algoritma K-Means. Aplikasi ini dirancang untuk mendukung penelitian akademik (skripsi/jurnal) Program Studi Teknik Informatika.

---

## Daftar Isi

1. [Latar Belakang](#latar-belakang)
2. [Tujuan Penelitian](#tujuan-penelitian)
3. [Arsitektur Sistem](#arsitektur-sistem)
4. [Teknologi yang Digunakan](#teknologi-yang-digunakan)
5. [Struktur Proyek](#struktur-proyek)
6. [Instalasi dan Konfigurasi](#instalasi-dan-konfigurasi)
7. [Penggunaan Aplikasi](#penggunaan-aplikasi)
8. [Metodologi Penelitian](#metodologi-penelitian)
9. [Validasi Cluster](#validasi-cluster)
10. [Dokumentasi API](#dokumentasi-api)
11. [Referensi](#referensi)

---

## Latar Belakang

Perkembangan teknologi informasi yang pesat tidak hanya membawa dampak positif, tetapi juga memicu munculnya kejahatan baru berbasis siber. Berdasarkan laporan Patroli Siber Polri dan Direktorat Tindak Pidana Siber Bareskrim, jumlah laporan cybercrime di Indonesia menunjukkan tren peningkatan setiap tahunnya. Kompleksitas data laporan kejahatan siber—yang mencakup dimensi waktu, lokasi geografis, jenis modus operandi, kerugian materiil, hingga profil korban—membuat analisis manual menjadi tidak efisien.

Algoritma **K-Means Clustering** merupakan salah satu metode _unsupervised learning_ yang paling banyak digunakan untuk mengidentifikasi pola tersembunyi dalam data tanpa label. Penelitian ini mengembangkan sebuah sistem informasi yang mampu melakukan pengelompokan otomatis terhadap data cybercrime sehingga karakteristik dominan dari setiap kelompok dapat dimanfaatkan untuk pengambilan kebijakan, alokasi sumber daya penindakan, serta edukasi publik.

## Tujuan Penelitian

1. Merancang sistem informasi terintegrasi untuk pengelolaan data laporan cybercrime.
2. Mengimplementasikan algoritma K-Means Clustering untuk mengelompokkan data berdasarkan karakteristik multi-dimensi.
3. Menentukan jumlah cluster optimal menggunakan tiga metrik validasi: **Elbow Method (WCSS)**, **Silhouette Coefficient**, dan **Davies–Bouldin Index**.
4. Memvisualisasikan hasil pengelompokan dan tren temporal-geografis dalam dashboard interaktif.
5. Menghasilkan laporan profil cluster yang dapat diekspor dalam format PDF/Excel sebagai bahan pengambilan keputusan.

## Arsitektur Sistem

Sistem dirancang dengan pendekatan **microservice** yang memisahkan _presentation layer_ (Laravel) dari _machine learning layer_ (FastAPI) untuk menjaga _separation of concerns_ dan skalabilitas.

```
┌────────────────────────────────────────────────────────────────┐
│                        Pengguna (Browser)                       │
└────────────────────────────────┬────────────────────────────────┘
                                 │ HTTPS
                                 ▼
┌────────────────────────────────────────────────────────────────┐
│                   Laravel 11 (Application Layer)                │
│   • Autentikasi & RBAC (Admin / Analis / Viewer)                │
│   • CRUD Data Cybercrime + Import CSV (Laravel Excel)           │
│   • Dashboard Visual (Chart.js, Leaflet.js)                     │
│   • Service Layer ──── HTTP Client ────► ML Service             │
│   • Laporan PDF (DomPDF) & Excel (PhpSpreadsheet)               │
└──────┬───────────────────────────────────────────────┬─────────┘
       │ Eloquent ORM                                  │ REST/JSON
       ▼                                               ▼
┌────────────────────┐                  ┌──────────────────────────┐
│   MySQL 8.0         │                  │ FastAPI ML Service       │
│   • cybercrimes     │                  │ • /api/v1/elbow          │
│   • clustering_runs │                  │ • /api/v1/cluster        │
│   • cluster_assigns │                  │ • /api/v1/predict        │
│   • users + roles   │                  │ • /api/v1/metrics        │
└────────────────────┘                  │ Stack: scikit-learn,      │
                                        │   pandas, numpy, PCA      │
                                        └──────────────────────────┘
```

## Teknologi yang Digunakan

| Lapisan          | Teknologi                          | Versi   | Justifikasi                                                                 |
| ---------------- | ---------------------------------- | ------- | --------------------------------------------------------------------------- |
| Frontend         | Blade + Tailwind CSS + Alpine.js   | 11.x    | Server-rendered, ringan, mendukung interaktivitas tanpa SPA framework berat |
| Visualisasi      | Chart.js, Leaflet.js, D3.js        | latest  | Render chart performant, peta tematik, dan custom viz                       |
| Backend Web      | Laravel                            | 11.x    | Ekosistem matang, MVC, _service container_, _Eloquent ORM_                  |
| Backend ML       | FastAPI (Python)                   | 0.115.x | Async, type-safe, OpenAPI otomatis, performa tinggi                         |
| ML Library       | scikit-learn, pandas, numpy        | 1.5+    | Standar industri untuk machine learning                                     |
| Database         | MySQL                              | 8.0     | RDBMS open-source, dukungan JSON column                                     |
| Cache & Queue    | Redis (opsional)                   | 7.x     | Mempercepat dashboard analitik                                              |
| Containerisasi   | Docker + Docker Compose            | 24+     | Replikabel, lingkungan deterministik                                        |
| Testing          | PHPUnit, pytest                    | latest  | Unit & integration test                                                     |

## Struktur Proyek

```
CYBERCRIME-K-means-clusterring/
├── laravel-app/              # Aplikasi Laravel 11
│   ├── app/
│   │   ├── Http/Controllers/ # Controllers: Auth, Dashboard, Cybercrime, Analysis, Report
│   │   ├── Models/           # Eloquent Models
│   │   ├── Services/         # MLServiceClient, ClusteringService, ReportService
│   │   ├── Imports/          # CSV importer
│   │   └── Exports/          # Excel/PDF exporters
│   ├── database/
│   │   ├── migrations/       # Schema
│   │   ├── seeders/          # Data sintetik cybercrime Indonesia 2019–2024
│   │   └── factories/
│   ├── resources/views/      # Blade templates
│   ├── routes/               # web.php, api.php
│   └── config/
├── ml-service/               # FastAPI ML microservice
│   ├── app/
│   │   ├── main.py           # FastAPI entrypoint
│   │   ├── clustering.py     # K-Means + Elbow + Silhouette + DB Index
│   │   ├── preprocessing.py  # Encoding, scaling, feature engineering
│   │   ├── visualization.py  # PCA 2D projection
│   │   └── schemas.py        # Pydantic models
│   ├── requirements.txt
│   └── Dockerfile
├── data/
│   └── sample-cybercrime.csv # Dataset sintetik (1.500 record)
├── docs/
│   ├── METODOLOGI.md         # Metodologi penelitian (CRISP-DM)
│   ├── API.md                # Spesifikasi REST API
│   ├── ARSITEKTUR.md         # Detail desain sistem
│   └── PANDUAN-PENGGUNAAN.md # User guide
├── docker/                   # Dockerfile & nginx config
├── docker-compose.yml
└── README.md                 # File ini
```

## Instalasi dan Konfigurasi

### Prasyarat

- Docker & Docker Compose
- (Opsional, untuk instalasi non-Docker) PHP 8.2+, Composer 2.x, Node.js 20+, Python 3.11+, MySQL 8.0

### Cara 1 — Menggunakan Docker (Disarankan)

```bash
# 1. Clone repository
git clone https://github.com/zillanoctane/CYBERCRIME-K-means-clusterring.git
cd CYBERCRIME-K-means-clusterring

# 2. Salin file environment
cp .env.example .env
cp laravel-app/.env.example laravel-app/.env

# 3. Build & jalankan container
docker compose up -d --build

# 4. Inisialisasi aplikasi Laravel (sekali saja)
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link

# 5. Build asset frontend
docker compose exec app npm install
docker compose exec app npm run build
```

Aplikasi akan tersedia di:
- **Web**: http://localhost:8000
- **ML Service (Swagger)**: http://localhost:8001/docs
- **MySQL**: localhost:3306

### Akun Default

| Peran   | Email                  | Password    |
| ------- | ---------------------- | ----------- |
| Admin   | admin@siancek.test     | password    |
| Analis  | analis@siancek.test    | password    |
| Viewer  | viewer@siancek.test    | password    |

> **Penting:** Ganti password default sebelum deployment produksi.

### Cara 2 — Manual (tanpa Docker)

Lihat [docs/PANDUAN-INSTALASI-MANUAL.md](docs/PANDUAN-INSTALASI-MANUAL.md).

## Penggunaan Aplikasi

1. **Login** menggunakan akun yang telah disediakan.
2. **Import Data** — Buka menu _Data Cybercrime → Import CSV_, unggah file mengikuti format yang ada di `data/sample-cybercrime.csv`.
3. **Konfigurasi Analisis** — Buka _Analisis → Buat Analisis Baru_, pilih fitur, rentang waktu, dan nilai K (atau biarkan sistem menentukan K optimal otomatis via Elbow + Silhouette).
4. **Jalankan Clustering** — Sistem akan mengirim data ke service FastAPI; hasil disimpan di database dan ditampilkan dalam dashboard.
5. **Interpretasi Cluster** — Periksa _profil cluster_ (statistik per fitur), _scatter plot PCA_, dan _trend cluster_ antar waktu.
6. **Ekspor Laporan** — Unduh hasil dalam format PDF atau Excel.

## Metodologi Penelitian

Penelitian ini mengikuti kerangka kerja **CRISP-DM** (Cross-Industry Standard Process for Data Mining) yang terdiri dari enam fase:

1. **Business Understanding** — Identifikasi kebutuhan pengelompokan karakteristik cybercrime sebagai dasar perumusan kebijakan.
2. **Data Understanding** — Eksplorasi data laporan cybercrime (statistik deskriptif, missing value, outlier).
3. **Data Preparation** —
   - _Cleaning_: penanganan nilai null dengan median imputation, deteksi outlier dengan IQR.
   - _Encoding_: One-Hot Encoding untuk variabel kategorikal (jenis kejahatan, provinsi, modus).
   - _Scaling_: StandardScaler agar setiap fitur memiliki mean=0 dan std=1.
   - _Reduksi Dimensi_ (opsional untuk visualisasi): PCA → 2 komponen.
4. **Modeling** — K-Means dengan inisialisasi `k-means++` dan `n_init=10` (Lloyd's algorithm).
5. **Evaluation** — Validasi internal cluster (lihat bagian Validasi).
6. **Deployment** — Sistem informasi web yang dapat diakses _stakeholder_.

Detail lengkap terdapat pada [docs/METODOLOGI.md](docs/METODOLOGI.md).

## Validasi Cluster

Pemilihan jumlah cluster optimal (`K`) dilakukan dengan tiga indikator komplementer:

| Metrik                  | Rumus / Konsep                                            | Interpretasi                                  |
| ----------------------- | --------------------------------------------------------- | --------------------------------------------- |
| **WCSS (Elbow)**        | Σ Σ ‖xᵢ − μⱼ‖²                                            | Cari _siku_ pada plot K vs WCSS               |
| **Silhouette Coeff.**   | s(i) = (b − a) / max(a, b), rentang [−1, 1]               | Semakin mendekati 1 semakin baik              |
| **Davies–Bouldin Idx.** | rata-rata rasio jarak intra-cluster terhadap inter-cluster | Semakin kecil semakin baik                    |

K optimal direkomendasikan sistem berdasarkan kombinasi ketiganya, namun pengguna tetap dapat menentukan K secara manual.

## Dokumentasi API

Spesifikasi lengkap REST API tersedia di [docs/API.md](docs/API.md) dan dapat diakses interaktif via Swagger UI pada http://localhost:8001/docs.

Ringkasan endpoint ML Service:

| Method | Path                       | Deskripsi                                            |
| ------ | -------------------------- | ---------------------------------------------------- |
| GET    | `/health`                  | Healthcheck service                                  |
| POST   | `/api/v1/elbow`            | Hitung WCSS untuk rentang K (default 2–10)           |
| POST   | `/api/v1/cluster`          | Eksekusi K-Means dengan K yang ditentukan            |
| POST   | `/api/v1/predict`          | Prediksi cluster untuk data baru menggunakan model   |
| POST   | `/api/v1/metrics`          | Hitung silhouette & DB index untuk hasil clustering  |

## Referensi

1. MacQueen, J. (1967). _Some methods for classification and analysis of multivariate observations_. Proceedings of the 5th Berkeley Symposium on Mathematical Statistics and Probability.
2. Rousseeuw, P. J. (1987). _Silhouettes: A graphical aid to the interpretation and validation of cluster analysis_. Journal of Computational and Applied Mathematics, 20, 53–65.
3. Davies, D. L., & Bouldin, D. W. (1979). _A cluster separation measure_. IEEE Transactions on Pattern Analysis and Machine Intelligence, PAMI-1(2), 224–227.
4. Wirth, R., & Hipp, J. (2000). _CRISP-DM: Towards a standard process model for data mining_. Proceedings of the 4th International Conference on the Practical Applications of Knowledge Discovery and Data Mining.
5. Pedregosa, F. et al. (2011). _Scikit-learn: Machine Learning in Python_. JMLR, 12, 2825–2830.
6. Han, J., Kamber, M., & Pei, J. (2011). _Data Mining: Concepts and Techniques_ (3rd ed.). Morgan Kaufmann.
7. Direktorat Tindak Pidana Siber Bareskrim Polri. (2024). _Laporan Statistik Kejahatan Siber Indonesia 2019–2023_.

## Lisensi

Proyek ini dikembangkan untuk keperluan penelitian akademik. Hak cipta tetap berada pada penulis.

---

**Pengembang**: [Nama Mahasiswa]  
**Program Studi**: Teknik Informatika  
**Universitas**: [Nama Universitas]  
**Tahun**: 2026
