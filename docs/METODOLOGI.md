# Metodologi Penelitian — SIANCEK

Dokumen ini menguraikan kerangka kerja metodologis yang digunakan oleh aplikasi SIANCEK dalam melakukan analisis pengelompokan karakteristik dan tren tindak pidana siber. Penyusunan mengacu pada kerangka kerja **CRISP-DM** (Wirth & Hipp, 2000) yang lazim dipakai dalam penelitian _data mining_ berbasis industri maupun akademik.

---

## 1. Kerangka CRISP-DM

CRISP-DM membagi proses _data mining_ menjadi enam fase iteratif. Implementasi SIANCEK terhadap masing-masing fase diuraikan di bawah ini.

### 1.1 Business Understanding

Permasalahan yang diangkat:

> Bagaimana mengelompokkan laporan tindak pidana siber berdasarkan karakteristik multi-dimensinya (jenis kejahatan, modus, lokasi, dampak, profil korban) sehingga pola dominan dapat diidentifikasi untuk mendukung pengambilan kebijakan?

Tujuan analitis:

1. Mengidentifikasi cluster (kelompok) kasus dengan karakteristik serupa.
2. Mengukur kualitas pemisahan antar cluster secara objektif.
3. Memetakan tren temporal dan geografis tiap cluster.

### 1.2 Data Understanding

Unit data adalah satu laporan tindak pidana siber dengan atribut sebagai berikut (lihat _migration_ `create_cybercrime_records_table.php`):

| Kategori          | Atribut                                                                              |
| ----------------- | ------------------------------------------------------------------------------------ |
| Identitas         | `nomor_laporan`, `tanggal_kejadian`, `tanggal_laporan`                               |
| Klasifikasi       | `jenis_kejahatan`, `sub_jenis`, `modus_operandi`, `platform`                         |
| Geografis         | `provinsi`, `kota_kabupaten`, `latitude`, `longitude`                                |
| Profil Korban     | `usia_korban`, `jenis_kelamin_korban`, `pekerjaan_korban`, `pendidikan_korban`       |
| Dampak            | `estimasi_kerugian`, `jumlah_korban`, `tingkat_keparahan`                            |
| Status & Sumber   | `status_kasus`, `tersangka_teridentifikasi`, `sumber_data`                           |

Eksplorasi awal dilakukan melalui Dashboard (lihat `DashboardController` dan view `dashboard/index.blade.php`) yang menampilkan tren bulanan, tren tahunan, distribusi jenis, dan top provinsi.

### 1.3 Data Preparation

Implementasi terdapat di `ml-service/app/preprocessing.py`. Langkah:

1. **Cleaning / Imputation**
   - Numerik: `SimpleImputer(strategy="median")` — robust terhadap outlier dibanding `mean`.
   - Kategorikal: `SimpleImputer(strategy="most_frequent")`.
2. **Encoding**
   - Kategorikal di-_One-Hot Encoded_ dengan `OneHotEncoder(handle_unknown="ignore")` agar nilai kategori yang muncul saat inferensi tetapi tidak ada saat training tidak menyebabkan error.
3. **Scaling**
   - Default: `StandardScaler` (Z-score) — membuat tiap fitur memiliki μ = 0 dan σ = 1. Alternatif: `MinMaxScaler` dan `RobustScaler` (basis IQR).
4. **Feature Engineering** (Laravel side, `ClusteringService::buildDataset`)
   - `keparahan_score` ∈ {1, 2, 3, 4} — ordinal mapping dari `tingkat_keparahan`.
   - `durasi_lapor_hari` — selisih `tanggal_kejadian` dan `tanggal_laporan`.

> **Catatan keamanan**: nama fitur yang diizinkan terdaftar dalam _whitelist_ `ALLOWED_NUMERIC` dan `ALLOWED_CATEGORICAL` di `ClusteringService`. Hal ini mencegah _SQL injection_ via parameter fitur dari klien.

### 1.4 Modeling

Algoritma utama: **K-Means** (MacQueen, 1967) dengan inisialisasi **k-means++** (Arthur & Vassilvitskii, 2007).

```python
KMeans(
    n_clusters=K,
    init="k-means++",
    n_init=10,        # 10 kali inisialisasi acak, ambil hasil terbaik
    max_iter=300,
    tol=1e-4,
    random_state=42,
    algorithm="lloyd",
)
```

#### Mengapa K-Means?

| Keunggulan                                  | Kelemahan & Mitigasi                                   |
| ------------------------------------------- | ------------------------------------------------------ |
| Komputasi cepat untuk dataset besar         | Sensitif terhadap skala → diatasi dengan StandardScaler |
| Mudah diinterpretasikan (centroid eksplisit) | Sensitif outlier → opsi RobustScaler tersedia          |
| Hasil deterministik dengan random_state     | Asumsi cluster sferis → divisualisasikan via PCA      |
| Dukungan luas di scikit-learn               | Perlu menentukan K → dilengkapi Elbow & Silhouette    |

### 1.5 Evaluation

Tiga indikator validasi internal cluster dihitung (lihat `clustering.py`):

#### a. Silhouette Coefficient (Rousseeuw, 1987)

$$s(i) = \frac{b(i) - a(i)}{\max\{a(i), b(i)\}}, \quad s \in [-1, 1]$$

dengan $a(i)$ = rata-rata jarak titik $i$ ke anggota cluster yang sama, $b(i)$ = rata-rata jarak ke cluster terdekat berbeda. Nilai mendekati 1 menunjukkan cluster yang baik.

**Pedoman interpretasi** (Kaufman & Rousseeuw, 1990):
- s ≥ 0.71 — struktur kuat
- 0.51 ≤ s ≤ 0.70 — struktur masuk akal
- 0.26 ≤ s ≤ 0.50 — struktur lemah
- s ≤ 0.25 — tidak ada struktur substansial

#### b. Davies–Bouldin Index (Davies & Bouldin, 1979)

$$DB = \frac{1}{k}\sum_{i=1}^{k}\max_{j \neq i}\left(\frac{\sigma_i + \sigma_j}{d(c_i, c_j)}\right)$$

dengan $\sigma_i$ = rata-rata jarak intra-cluster $i$, $d(c_i,c_j)$ = jarak antar centroid. **Semakin kecil semakin baik** — nilai < 1 umumnya menunjukkan pemisahan yang baik.

#### c. Calinski–Harabasz Index (Caliński & Harabasz, 1974)

Rasio variansi antar-cluster terhadap variansi dalam-cluster (variance ratio criterion). **Semakin besar semakin baik**.

### 1.6 Deployment

Sistem ini-sendiri merupakan _deployment_-nya: aplikasi web Laravel yang berinteraksi dengan ML microservice via REST API. Hasil setiap _run_ disimpan di database (tabel `clustering_runs` dan `cluster_assignments`) sehingga dapat diaudit dan direproduksi.

---

## 2. Penentuan Jumlah Cluster Optimal

SIANCEK menyediakan pratinjau Elbow + Silhouette + Davies-Bouldin sebelum eksekusi clustering. Strategi rekomendasi otomatis (`clustering.recommend_k`):

1. Hitung _second-difference_ kurva WCSS untuk mendeteksi titik siku (proksi sederhana untuk **Kneedle algorithm**; Satopaa et al., 2011).
2. Rangking masing-masing K berdasarkan:
   - Silhouette (descending)
   - Davies-Bouldin (ascending)
   - Calinski-Harabasz (descending)
3. Pilih K dengan _total rank_ terkecil.
4. Bandingkan dengan titik siku: jika konsisten, keyakinan tinggi; jika tidak, prioritaskan rangking komposit dengan menampilkan keduanya.

Pengguna selalu dapat _override_ rekomendasi sistem secara manual.

---

## 3. Reproducibility

Untuk memenuhi prinsip _reproducible research_:

1. **`random_state`** disimpan sebagai bagian dari `clustering_runs`. Eksekusi ulang dengan parameter yang sama dan dataset yang sama akan menghasilkan cluster yang identik.
2. **Konfigurasi lengkap** (fitur, filter, scaler) disimpan dalam JSON kolom `clustering_runs.filter` dan field-field lain.
3. **Versi dataset** ditentukan oleh `jumlah_data` yang disnapshot saat run.
4. **Logging aktivitas** di tabel `activity_logs`.

---

## 4. Limitasi Metodologi

Penting untuk disebutkan dalam pembahasan jurnal:

1. **K-Means mengasumsikan cluster sferis** dengan ukuran serupa. Cluster berbentuk non-konveks tidak akan terdeteksi optimal. Alternatif: DBSCAN, HDBSCAN, atau Gaussian Mixture Models — yang dapat menjadi penelitian lanjutan.
2. **Sensitif terhadap pemilihan fitur**: fitur yang tidak informatif dapat menutupi struktur klastering yang sesungguhnya.
3. **Validitas internal tidak menjamin validitas eksternal**: cluster yang "secara matematis baik" belum tentu bermakna secara substantif tanpa _domain expertise_.
4. **Data sintetik vs. data nyata**: dataset bawaan adalah sintetik untuk demo. Penerapan pada data nyata memerlukan persetujuan etis dan _data sharing agreement_ dengan instansi terkait (Polri, BSSN, Kominfo).

---

## 5. Etika Penelitian

- Sistem **tidak menyimpan data pribadi korban** secara identifikatif (PII) di luar atribut demografis yang sudah dianonimkan.
- Akses sistem dibatasi melalui RBAC tiga tingkat (Admin / Analis / Viewer).
- Audit trail seluruh aktivitas disimpan di `activity_logs`.
- Penerapan pada data riil harus tunduk pada UU Perlindungan Data Pribadi No. 27/2022.

---

## 6. Referensi

- Arthur, D., & Vassilvitskii, S. (2007). _k-means++: The advantages of careful seeding_. Proc. ACM-SIAM SODA, 1027–1035.
- Caliński, T., & Harabasz, J. (1974). _A dendrite method for cluster analysis_. Communications in Statistics, 3(1), 1–27.
- Davies, D. L., & Bouldin, D. W. (1979). _A cluster separation measure_. IEEE TPAMI, 1(2), 224–227.
- Han, J., Kamber, M., & Pei, J. (2011). _Data Mining: Concepts and Techniques_ (3rd ed.). Morgan Kaufmann.
- Kaufman, L., & Rousseeuw, P. J. (1990). _Finding Groups in Data: An Introduction to Cluster Analysis_. Wiley.
- MacQueen, J. (1967). _Some methods for classification and analysis of multivariate observations_. Proc. Berkeley Symposium.
- Pedregosa, F. et al. (2011). _Scikit-learn: Machine Learning in Python_. JMLR, 12, 2825–2830.
- Rousseeuw, P. J. (1987). _Silhouettes: a graphical aid to the interpretation and validation of cluster analysis_. Journal of Computational and Applied Mathematics, 20, 53–65.
- Satopaa, V., Albrecht, J., Irwin, D., & Raghavan, B. (2011). _Finding a "kneedle" in a haystack: Detecting knee points in system behavior_. ICDCSW 2011.
- Wirth, R., & Hipp, J. (2000). _CRISP-DM: Towards a standard process model for data mining_. Proc. PAKDD 2000.
