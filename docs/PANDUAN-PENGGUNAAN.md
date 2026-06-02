# Panduan Penggunaan SIANCEK

Dokumen ini ditujukan untuk pengguna akhir (peneliti, analis, mahasiswa) yang ingin menggunakan SIANCEK untuk analisis cluster cybercrime.

## Daftar Isi

1. [Login dan Peran Pengguna](#1-login-dan-peran-pengguna)
2. [Mengenal Dashboard](#2-mengenal-dashboard)
3. [Mengelola Data Cybercrime](#3-mengelola-data-cybercrime)
4. [Import Data dari CSV](#4-import-data-dari-csv)
5. [Membuat Analisis Cluster](#5-membuat-analisis-cluster)
6. [Menginterpretasikan Hasil](#6-menginterpretasikan-hasil)
7. [Ekspor Laporan](#7-ekspor-laporan)
8. [Tips untuk Sidang Skripsi & Jurnal](#8-tips-untuk-sidang-skripsi--jurnal)

---

## 1. Login dan Peran Pengguna

Tiga akun demo tersedia setelah `php artisan db:seed`:

| Email                  | Peran   | Hak Akses                                                              |
| ---------------------- | ------- | ---------------------------------------------------------------------- |
| admin@siancek.test     | Admin   | Semua hak, termasuk hapus data dan analisis                            |
| analis@siancek.test    | Analis  | Tambah/edit data & analisis, lihat semua, **tidak bisa hapus**         |
| viewer@siancek.test    | Viewer  | Hanya melihat dashboard, data, dan hasil analisis                       |

Semua password default: `password`. **Ganti sebelum produksi.**

## 2. Mengenal Dashboard

Dashboard utama menampilkan:

- **4 metrik utama**: total laporan, laporan tahun berjalan, total kerugian YTD, jumlah analisis sukses.
- **Tren bulanan** (24 bulan terakhir): line chart untuk identifikasi musiman.
- **Distribusi jenis kejahatan**: doughnut chart 10 jenis teratas.
- **Tren tahunan**: bar chart perkembangan year-over-year.
- **Top 10 provinsi**: distribusi geografis.
- **Daftar 5 analisis terbaru** untuk akses cepat.

## 3. Mengelola Data Cybercrime

### Menambah secara manual

1. Menu **Data Cybercrime → Tambah Laporan**.
2. Isi minimal field bertanda asterisk merah.
3. Klik **Simpan**.

### Memfilter & Mencari

Halaman index menyediakan filter:
- Kata kunci (No. laporan / jenis / provinsi / modus)
- Jenis kejahatan
- Provinsi
- Rentang tanggal kejadian

### Mengedit / Menghapus

- **Edit** tersedia untuk Admin & Analis.
- **Hapus** hanya untuk Admin (soft-delete; record dapat di-restore via tinker bila diperlukan).

## 4. Import Data dari CSV

1. Menu **Data Cybercrime → Import CSV**.
2. Unduh contoh format di `data/sample-cybercrime.csv` (1.500 baris siap pakai).
3. Pastikan header berikut ada (tidak case-sensitive):

   ```
   nomor_laporan, tanggal_kejadian, tanggal_laporan, jenis_kejahatan, sub_jenis,
   modus_operandi, platform, provinsi, kota_kabupaten, latitude, longitude,
   usia_korban, jenis_kelamin_korban, pekerjaan_korban, pendidikan_korban,
   estimasi_kerugian, jumlah_korban, tingkat_keparahan, status_kasus,
   tersangka_teridentifikasi, sumber_data, keterangan
   ```

4. Format tanggal: `YYYY-MM-DD` atau format Excel native.
5. Sistem akan **upsert** berdasarkan `nomor_laporan` (duplikasi ditimpa).
6. Baris yang gagal divalidasi akan dilewati; jumlah berhasil/gagal ditampilkan setelah import.

## 5. Membuat Analisis Cluster

Buka **Analisis → Analisis Baru**. Formulir terbagi 4 langkah:

### Step 1 — Identitas
- **Nama**: deskriptif (mis. *"Klasterisasi Cybercrime Indonesia 2024"*).
- **Random state**: biarkan `42` untuk konsistensi dengan literatur.

### Step 2 — Pemilihan Fitur

Pilih fitur sesuai pertanyaan penelitian. Saran umum:

| Tujuan Penelitian                         | Fitur Numerik Disarankan                                            | Fitur Kategorikal             |
| ----------------------------------------- | ------------------------------------------------------------------- | ----------------------------- |
| Karakteristik dampak                       | `estimasi_kerugian`, `jumlah_korban`, `keparahan_score`              | `jenis_kejahatan`              |
| Karakteristik geografis & jenis            | `keparahan_score`                                                   | `jenis_kejahatan`, `provinsi` |
| Modus operandi & target                   | `usia_korban`, `keparahan_score`                                    | `modus_operandi`, `pendidikan_korban` |

> Jangan memilih terlalu banyak kategorikal sekaligus — One-Hot menyebabkan _curse of dimensionality_.

### Step 3 — Filter Subset (opsional)

Batasi rentang tanggal, jenis, atau provinsi jika ingin analisis spesifik (mis. "hanya tahun 2023" atau "hanya provinsi pulau Jawa").

### Step 4 — Tentukan K

1. Klik **Pratinjau Elbow** untuk menghitung kurva WCSS + Silhouette + DB Index untuk K=2..10.
2. Sistem akan **merekomendasikan K** berdasarkan kombinasi tiga metrik.
3. Anda dapat mempertimbangkan rekomendasi atau menentukan K sendiri.
4. Klik **Jalankan Clustering**.

Eksekusi biasanya selesai dalam 1–10 detik untuk dataset ≤10.000 baris.

## 6. Menginterpretasikan Hasil

Halaman hasil analisis menampilkan 6 bagian:

### a) Lima metrik utama
- **K**: jumlah cluster final
- **Silhouette**: 0–1, semakin tinggi semakin baik
- **Davies-Bouldin**: semakin kecil semakin baik
- **Calinski-Harabasz**: semakin besar semakin baik
- **Iterasi & Inertia**: indikator konvergensi

### b) Visualisasi PCA 2D
Setiap titik = 1 laporan, diwarnai berdasarkan cluster. Cluster yang baik akan terlihat _terpisah_ secara visual.

### c) Distribusi anggota (doughnut)
Mengecek apakah ada cluster yang terlalu besar/kecil. Cluster yang berisi <5% data perlu ditelaah.

### d) Feature importance
Fitur yang paling membedakan cluster (variansi inter-cluster tinggi). Membantu menulis bagian "diskusi" jurnal.

### e) Interpretasi metrik (progress bar)
Otomatis menyajikan narasi kualitas cluster — bisa dijadikan acuan kalimat di bab "Hasil dan Pembahasan".

### f) Profil tiap cluster
**Bagian terpenting untuk skripsi/jurnal**. Setiap kartu cluster menampilkan:
- Ukuran & proporsi
- Narasi otomatis ("Cluster ini didominasi oleh jenis_kejahatan=Pemerasan Siber...")
- Kategori dominan
- Rata-rata fitur numerik

Anda dapat memberi *label kualitatif* pada tiap cluster, mis.:
- Cluster 0 → "Cybercrime Dampak Tinggi Profil Korporat"
- Cluster 1 → "Penipuan Volume Tinggi Dampak Rendah"
- dst.

## 7. Ekspor Laporan

Pada halaman hasil analisis, tombol di pojok kanan atas:

- **Unduh PDF** — laporan akademik 1–3 halaman, siap dimasukkan ke skripsi sebagai lampiran.
- **Unduh Excel** — penugasan cluster per record + koordinat PCA, untuk analisis lanjutan di tools lain (R, SPSS, Python).

## 8. Tips untuk Sidang Skripsi & Jurnal

1. **Reproducibility**: catat `random_state`, daftar fitur, scaler, filter, dan K final. Semua disimpan otomatis di tabel `clustering_runs`.
2. **Jangan hanya melaporkan Silhouette**. Tampilkan ketiga metrik (Silhouette + DB + CH) untuk menunjukkan robustness.
3. **Bandingkan beberapa K**. Simpan setidaknya 3 run dengan K berbeda untuk memperkuat justifikasi pemilihan K final.
4. **Beri label kualitatif** untuk tiap cluster di bab pembahasan; jangan biarkan cluster hanya bernama "Cluster 0, 1, 2..."
5. **Validasi domain**: konfirmasi profil cluster ke dosen pembimbing atau ahli kriminologi sebelum publikasi.
6. **Limitasi**: tulis di bab penutup bahwa K-Means mengasumsikan cluster sferis; penelitian lanjutan dapat memakai DBSCAN/HDBSCAN.
7. **Etika & Data**: jika menggunakan data nyata, lampirkan _ethical clearance_ dan _data sharing agreement_.

---

Selamat menggunakan SIANCEK. Semoga membantu kelancaran penelitian Anda.
