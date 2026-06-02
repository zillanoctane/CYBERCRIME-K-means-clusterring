"""Generator dataset sintetik cybercrime Indonesia.

Output: data/sample-cybercrime.csv (~1500 baris)

Karakteristik dataset:
    - Periode: 2019-01 sampai 2024-12 dengan tren naik
    - Distribusi jenis kejahatan mengikuti rasio yang plausible (Penipuan Online dominan)
    - Kerugian, jumlah korban, dan keparahan berkorelasi dengan jenis kejahatan
      (mis. ransomware: kerugian tinggi; hoaks: kerugian rendah)
    - Lokasi tersebar pada 18 provinsi besar di Indonesia

Catatan: data ini SINTETIK; nilai-nilai numerik diacak agar TIDAK
merepresentasikan kasus nyata.

Untuk regenerasi:
    python3 data/generate_sample.py
"""
from __future__ import annotations

import csv
import random
from datetime import date, timedelta
from pathlib import Path

random.seed(42)

OUT = Path(__file__).resolve().parent / "sample-cybercrime.csv"

JENIS = {
    "Penipuan Online":     {"weight": 35, "sub": ["Investasi Bodong", "Jual-Beli Fiktif", "Penipuan Pinjol Ilegal", "Skema Ponzi"]},
    "Pencurian Data":      {"weight": 12, "sub": ["Data Pribadi", "Data Korporat", "Data Pemerintah"]},
    "Akses Ilegal":        {"weight": 10, "sub": ["Defacement", "Brute Force", "SQL Injection", "XSS"]},
    "Konten Asusila":      {"weight": 9,  "sub": ["Penyebaran Konten", "Pornografi Anak", "Sextortion"]},
    "Pencemaran Nama":     {"weight": 8,  "sub": ["Doxing", "Hate Speech", "Fitnah"]},
    "Judi Online":         {"weight": 8,  "sub": ["Slot Online", "Sportsbook", "Live Casino"]},
    "Hoaks/Disinformasi":  {"weight": 6,  "sub": ["Hoaks Politik", "Hoaks Kesehatan", "SARA"]},
    "Peretasan Akun":      {"weight": 6,  "sub": ["Phishing", "Sim Swap", "Social Engineering"]},
    "Pemerasan Siber":     {"weight": 4,  "sub": ["Ransomware", "Sextortion Bisnis"]},
    "Penyebaran Malware":  {"weight": 2,  "sub": ["Trojan", "Spyware", "Worm"]},
}

MODUS = {
    "Penipuan Online":    ["Phishing", "Social Engineering", "Fake Marketplace", "Modus Skema Ponzi", "Telepon Penipuan"],
    "Pencurian Data":     ["SQL Injection", "Phishing", "Insider Threat", "Credential Stuffing"],
    "Akses Ilegal":       ["Brute Force", "SQL Injection", "Exploit Vulnerability", "Default Credentials"],
    "Konten Asusila":     ["Penyebaran Massal", "Sextortion", "Pemerasan"],
    "Pencemaran Nama":    ["Akun Anonim", "Bot Spam", "Manipulasi Foto"],
    "Judi Online":        ["Aplikasi Pihak Ketiga", "Web Ilegal", "Telegram"],
    "Hoaks/Disinformasi": ["Forward Massal", "Akun Buzzer", "Manipulasi Foto"],
    "Peretasan Akun":     ["Phishing", "Sim Swap", "Social Engineering", "Password Lemah"],
    "Pemerasan Siber":    ["Ransomware", "Sextortion Bisnis", "DDoS Threat"],
    "Penyebaran Malware": ["Email Lampiran", "Software Bajakan", "USB Drop"],
}

PLATFORM = ["WhatsApp", "Instagram", "Facebook", "Telegram", "Email", "Website", "TikTok", "Twitter/X", "Marketplace", "SMS", "Telepon"]

PROVINSI = {
    "DKI Jakarta":                  {"w": 18, "lat": -6.2088, "lng": 106.8456},
    "Jawa Barat":                   {"w": 14, "lat": -6.9147, "lng": 107.6098},
    "Jawa Timur":                   {"w": 11, "lat": -7.2575, "lng": 112.7521},
    "Jawa Tengah":                  {"w": 9,  "lat": -6.9667, "lng": 110.4167},
    "Banten":                       {"w": 6,  "lat": -6.1198, "lng": 106.1503},
    "Sumatera Utara":               {"w": 6,  "lat": 3.5952,  "lng": 98.6722},
    "Sulawesi Selatan":             {"w": 5,  "lat": -5.1477, "lng": 119.4327},
    "Bali":                         {"w": 4,  "lat": -8.4095, "lng": 115.1889},
    "Sumatera Selatan":             {"w": 4,  "lat": -2.9909, "lng": 104.7567},
    "Daerah Istimewa Yogyakarta":   {"w": 4,  "lat": -7.7972, "lng": 110.3688},
    "Riau":                         {"w": 3,  "lat": 0.5071,  "lng": 101.4478},
    "Sumatera Barat":               {"w": 3,  "lat": -0.9471, "lng": 100.4172},
    "Lampung":                      {"w": 3,  "lat": -5.4500, "lng": 105.2667},
    "Kalimantan Timur":             {"w": 3,  "lat": 0.5071,  "lng": 117.1536},
    "Kalimantan Selatan":           {"w": 3,  "lat": -3.3194, "lng": 114.5908},
    "Sulawesi Utara":               {"w": 2,  "lat": 1.4748,  "lng": 124.8421},
    "Nusa Tenggara Barat":          {"w": 2,  "lat": -8.5833, "lng": 116.1167},
    "Papua":                        {"w": 1,  "lat": -2.5337, "lng": 140.7181},
}

PEKERJAAN = ["Karyawan Swasta", "Wiraswasta", "PNS", "Mahasiswa", "Pelajar", "IRT", "Petani", "Buruh", "Profesional", "Pensiunan", "Tidak Bekerja"]
PENDIDIKAN = ["SD", "SMP", "SMA", "D3", "S1", "S2", "S3", "TD"]
SUMBER = ["Laporan Masyarakat", "Patroli Siber", "Aduan BSSN", "Aduan Konten Kominfo", "Inisiatif Polri", "Kerjasama Antar Lembaga"]
STATUS = ["baru", "dalam_penyelidikan", "p21", "selesai", "dihentikan"]

PER_TAHUN = {2019: 150, 2020: 200, 2021: 240, 2022: 280, 2023: 320, 2024: 360}


def weighted(items, weights):
    return random.choices(items, weights=weights, k=1)[0]


def profil_dampak(jenis: str):
    if jenis == "Pemerasan Siber":
        return random.randint(50_000_000, 500_000_000), random.randint(1, 5), weighted(["sedang", "tinggi", "kritis"], [10, 60, 30])
    if jenis == "Pencurian Data":
        return random.randint(10_000_000, 200_000_000), random.randint(1, 50), weighted(["sedang", "tinggi", "kritis"], [20, 60, 20])
    if jenis == "Penipuan Online":
        return random.randint(500_000, 50_000_000), random.randint(1, 8), weighted(["rendah", "sedang", "tinggi"], [30, 55, 15])
    if jenis == "Judi Online":
        return random.randint(1_000_000, 30_000_000), random.randint(1, 3), weighted(["rendah", "sedang"], [60, 40])
    if jenis == "Peretasan Akun":
        return random.randint(1_000_000, 25_000_000), random.randint(1, 3), weighted(["sedang", "tinggi"], [70, 30])
    if jenis == "Akses Ilegal":
        return random.randint(5_000_000, 100_000_000), random.randint(1, 10), weighted(["sedang", "tinggi"], [55, 45])
    if jenis == "Penyebaran Malware":
        return random.randint(2_000_000, 80_000_000), random.randint(1, 20), weighted(["sedang", "tinggi", "kritis"], [30, 55, 15])
    if jenis == "Konten Asusila":
        return random.randint(0, 5_000_000), random.randint(1, 3), weighted(["sedang", "tinggi"], [60, 40])
    if jenis == "Pencemaran Nama":
        return random.randint(0, 2_000_000), random.randint(1, 2), weighted(["rendah", "sedang"], [70, 30])
    if jenis == "Hoaks/Disinformasi":
        return random.randint(0, 1_000_000), random.randint(1, 100), weighted(["rendah", "sedang"], [75, 25])
    return random.randint(0, 10_000_000), random.randint(1, 3), "sedang"


def main() -> None:
    today = date.today()
    rows = []
    counter = 1
    j_keys = list(JENIS.keys()); j_weights = [v["weight"] for v in JENIS.values()]
    p_keys = list(PROVINSI.keys()); p_weights = [v["w"] for v in PROVINSI.values()]

    for tahun, jumlah in PER_TAHUN.items():
        for _ in range(jumlah):
            jk = weighted(j_keys, j_weights)
            prov = weighted(p_keys, p_weights)
            month = random.randint(1, 12); day = random.randint(1, 28)
            try:
                tanggal_kejadian = date(tahun, month, day)
            except ValueError:
                continue
            if tanggal_kejadian > today:
                continue
            tanggal_laporan = tanggal_kejadian + timedelta(days=random.randint(0, 30))
            kerugian, korban, keparahan = profil_dampak(jk)
            rows.append({
                "nomor_laporan": f"LP/{tahun}/{counter:04d}/SBR",
                "tanggal_kejadian": tanggal_kejadian.isoformat(),
                "tanggal_laporan": tanggal_laporan.isoformat(),
                "jenis_kejahatan": jk,
                "sub_jenis": random.choice(JENIS[jk]["sub"]),
                "modus_operandi": random.choice(MODUS[jk]),
                "platform": random.choice(PLATFORM),
                "provinsi": prov,
                "kota_kabupaten": "",
                "latitude": round(PROVINSI[prov]["lat"] + random.uniform(-0.05, 0.05), 6),
                "longitude": round(PROVINSI[prov]["lng"] + random.uniform(-0.05, 0.05), 6),
                "usia_korban": random.randint(17, 65),
                "jenis_kelamin_korban": weighted(["L", "P", "TD"], [48, 50, 2]),
                "pekerjaan_korban": random.choice(PEKERJAAN),
                "pendidikan_korban": random.choice(PENDIDIKAN),
                "estimasi_kerugian": kerugian,
                "jumlah_korban": korban,
                "tingkat_keparahan": keparahan,
                "status_kasus": weighted(STATUS, [25, 35, 15, 15, 10]),
                "tersangka_teridentifikasi": "1" if random.random() < 0.35 else "0",
                "sumber_data": random.choice(SUMBER),
                "keterangan": "",
            })
            counter += 1

    with OUT.open("w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=list(rows[0].keys()))
        writer.writeheader()
        writer.writerows(rows)
    print(f"OK — wrote {len(rows)} rows to {OUT}")


if __name__ == "__main__":
    main()
