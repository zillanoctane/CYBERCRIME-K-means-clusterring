# Panduan Instalasi Manual (Tanpa Docker)

Gunakan panduan ini bila Anda tidak menggunakan Docker (mis. development di host langsung atau presentasi sidang di laptop yang tidak punya Docker).

## Prasyarat

- **PHP 8.2+** dengan ekstensi: pdo_mysql, mbstring, openssl, xml, zip, gd, intl, bcmath, fileinfo
- **Composer 2.x**
- **Node.js 20+** + npm (opsional — hanya bila ingin compile asset frontend)
- **Python 3.11+** + pip
- **MySQL 8.0** atau **MariaDB 10.6+**
- Git

## 1. Setup Database

```bash
mysql -u root -p <<'SQL'
CREATE DATABASE siancek CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'siancek'@'localhost' IDENTIFIED BY 'siancek_secret';
GRANT ALL PRIVILEGES ON siancek.* TO 'siancek'@'localhost';
FLUSH PRIVILEGES;
SQL
```

## 2. Setup ML Service (FastAPI)

```bash
cd ml-service
python3 -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate
pip install -r requirements.txt

export ML_API_KEY="siancek-dev-key"
uvicorn app.main:app --host 0.0.0.0 --port 8001 --reload
```

Verifikasi: `curl http://localhost:8001/health` harus mengembalikan `{"status":"ok",...}`.

## 3. Setup Laravel

Pada terminal terpisah:

```bash
cd laravel-app
cp .env.example .env

# Edit .env — sesuaikan koneksi DB & ML_SERVICE_URL
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=siancek
# DB_USERNAME=siancek
# DB_PASSWORD=siancek_secret
# ML_SERVICE_URL=http://localhost:8001
# ML_API_KEY=siancek-dev-key

composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link

# Mode development:
php artisan serve --host 0.0.0.0 --port 8000
```

Akses: `http://localhost:8000`. Login dengan akun demo yang ada di README.

## 4. Import Dataset Sample

Setelah login sebagai admin/analis:

1. Menu **Data Cybercrime → Import CSV**
2. Pilih file `data/sample-cybercrime.csv` (1.550 baris)
3. Tunggu hingga selesai (~5-10 detik)

Alternatif: dataset sudah otomatis dimuat oleh seeder (`CybercrimeSeeder`).

## 5. Menjalankan Tests

ML service:
```bash
cd ml-service
source venv/bin/activate
pytest -v
```

Laravel:
```bash
cd laravel-app
php artisan test
```

## Troubleshooting

| Gejala                                          | Penyebab Umum                        | Solusi                                                 |
| ----------------------------------------------- | ------------------------------------ | ------------------------------------------------------ |
| "ML service tidak dapat dijangkau"              | uvicorn tidak jalan / port salah     | Cek `curl http://localhost:8001/health`; sesuaikan `.env` |
| "401 Unauthorized" saat clustering              | `ML_API_KEY` Laravel ≠ ML service    | Samakan nilai di kedua `.env`                          |
| `SQLSTATE[HY000] [2002]`                        | MySQL tidak hidup                    | `sudo service mysql start`                             |
| `Class "Maatwebsite\Excel\Facades\Excel" ...`   | composer install belum dijalankan    | `composer install`                                     |
| Blade error "Class App\…\AppServiceProvider…"   | autoload belum dump                  | `composer dump-autoload`                               |
| Halaman blank putih                             | Permission storage/                  | `chmod -R 775 storage bootstrap/cache`                 |

## Production Notes

- Jalankan `php artisan optimize` (cache config + route + view).
- Pasang web server (Nginx + PHP-FPM) — jangan gunakan `php artisan serve` di produksi.
- Aktifkan HTTPS dan set `APP_DEBUG=false`, `APP_ENV=production`.
- Untuk ML service: gunakan `gunicorn` atau jalankan di belakang reverse proxy dengan beberapa worker uvicorn.
- Ganti `ML_API_KEY` ke nilai acak yang kuat (`openssl rand -hex 32`).
