<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Laporan Analisis Cluster - {{ $run->nama }}</title>
    <style>
        @page { margin: 24mm 18mm; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1f2937; font-size: 11px; line-height: 1.5; }
        h1, h2, h3 { font-family: 'DejaVu Sans', sans-serif; color: #1f2da9; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 18px 0 6px; padding-bottom: 4px; border-bottom: 2px solid #1f2da9; }
        h3 { font-size: 12px; margin: 14px 0 4px; color: #2540ed; }
        .muted { color: #6b7280; font-size: 10px; }
        .meta { margin: 8px 0 20px; font-size: 10px; color: #4b5563; }
        .meta div { margin-bottom: 2px; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 10px; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f3f4f6; font-weight: 600; color: #1f2937; }
        .metric-box { display: inline-block; width: 23%; margin: 0 1% 8px 0; padding: 8px; background: #f3f4ff; border-left: 3px solid #2540ed; }
        .metric-box .label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; }
        .metric-box .value { font-size: 16px; font-weight: bold; color: #1f2da9; margin-top: 2px; }
        .cluster-card { margin: 10px 0; padding: 10px; background: #fafbff; border-left: 3px solid #2540ed; }
        .cluster-card .title { font-weight: bold; color: #1f2da9; }
        .footer { position: fixed; bottom: -18mm; left: 0; right: 0; text-align: center; font-size: 9px; color: #9ca3af; }
        .header-bar { background: linear-gradient(135deg, #1f2da9, #06b6d4); height: 6px; margin-bottom: 14px; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; background: #1f2da9; color: #fff; font-size: 9px; }
    </style>
</head>
<body>

<div class="header-bar"></div>

<table style="width:100%; margin:0;">
    <tr>
        <td style="border:none; padding:0;">
            <h1>SIANCEK</h1>
            <div class="muted">Sistem Analisis Klasterisasi Cybercrime</div>
        </td>
        <td style="border:none; padding:0; text-align:right; vertical-align:top;">
            <div class="muted">No. Dokumen: SIANCEK/{{ $run->id }}/{{ date('Y') }}</div>
            <div class="muted">Tanggal: {{ $tanggal }}</div>
        </td>
    </tr>
</table>

<h2>Laporan Hasil Analisis Pengelompokan</h2>

<div class="meta">
    <div><strong>Nama Analisis:</strong> {{ $run->nama }}</div>
    <div><strong>Deskripsi:</strong> {{ $run->deskripsi ?: '—' }}</div>
    <div><strong>Dijalankan oleh:</strong> {{ $run->creator?->name ?? 'Sistem' }} · {{ $run->created_at->translatedFormat('d F Y, H:i') }} WIB</div>
</div>

<h3>1. Ringkasan Eksekutif</h3>
<div class="metric-box">
    <div class="label">Jumlah Cluster</div>
    <div class="value">{{ $run->n_clusters }}</div>
</div>
<div class="metric-box">
    <div class="label">Silhouette</div>
    <div class="value">{{ number_format($run->silhouette, 3) }}</div>
</div>
<div class="metric-box">
    <div class="label">Davies–Bouldin</div>
    <div class="value">{{ number_format($run->davies_bouldin, 3) }}</div>
</div>
<div class="metric-box">
    <div class="label">Jumlah Data</div>
    <div class="value">{{ number_format($run->jumlah_data) }}</div>
</div>

<h3>2. Konfigurasi Eksekusi</h3>
<table>
    <tr><th style="width:35%">Parameter</th><th>Nilai</th></tr>
    <tr><td>Mode penentuan K</td><td>{{ $run->mode }}</td></tr>
    <tr><td>Algoritma</td><td>K-Means++ (Lloyd) — scikit-learn</td></tr>
    <tr><td>Random state</td><td>{{ $run->random_state }}</td></tr>
    <tr><td>Iterasi konvergen</td><td>{{ $run->iterations }}</td></tr>
    <tr><td>Scaler</td><td>{{ $run->scaler }}</td></tr>
    <tr><td>Fitur numerik</td><td>{{ implode(', ', $run->fitur_numerik ?: ['—']) }}</td></tr>
    <tr><td>Fitur kategorikal</td><td>{{ implode(', ', $run->fitur_kategorikal ?: ['—']) }}</td></tr>
    <tr><td>Inertia (WCSS)</td><td>{{ number_format($run->inertia, 4) }}</td></tr>
    <tr><td>Calinski–Harabasz</td><td>{{ number_format($run->calinski_harabasz, 2) }}</td></tr>
</table>

<h3>3. Interpretasi Kualitas</h3>
<p>Hasil clustering ini memiliki <strong>Silhouette Coefficient {{ number_format($run->silhouette, 3) }}</strong> yang dikategorikan <span class="badge">{{ $run->qualityLabel() }}</span>.
@if ($run->silhouette >= 0.5)
    Struktur cluster yang dihasilkan menunjukkan separasi yang jelas antar kelompok.
@elseif ($run->silhouette >= 0.25)
    Struktur cluster cukup, namun masih terdapat ambiguitas batas; pertimbangkan penambahan fitur diskriminatif.
@else
    Struktur cluster lemah; disarankan revisi seleksi fitur, scaler, atau nilai K.
@endif
Nilai <strong>Davies–Bouldin Index sebesar {{ number_format($run->davies_bouldin, 3) }}</strong> mengonfirmasi penilaian tersebut (semakin kecil semakin baik).</p>

<h3>4. Profil Tiap Cluster</h3>
@foreach ($profiles as $p)
    <div class="cluster-card">
        <div class="title">Cluster {{ $p['cluster'] }} — {{ $p['size'] }} anggota ({{ number_format($p['proportion'] * 100, 1) }}%)</div>
        <p>{{ $p['summary'] }}</p>
        @if (!empty($p['dominant_categorical']))
            <strong>Kategori dominan:</strong>
            <ul style="margin:4px 0 4px 18px;">
                @foreach ($p['dominant_categorical'] as $k => $v)
                    <li>{{ $k }} = <em>{{ $v }}</em></li>
                @endforeach
            </ul>
        @endif
        @if (!empty($p['centroid']))
            <strong>Rata-rata fitur numerik:</strong>
            <table>
                <tr>@foreach ($p['centroid'] as $k => $v) <th>{{ $k }}</th> @endforeach</tr>
                <tr>@foreach ($p['centroid'] as $k => $v) <td>{{ number_format($v, 2) }}</td> @endforeach</tr>
            </table>
        @endif
    </div>
@endforeach

<h3>5. Feature Importance</h3>
<table>
    <tr><th>Fitur</th><th>Kontribusi</th><th>Bar</th></tr>
    @php $sorted = collect($importance)->sortDesc()->take(15); $max = $sorted->first() ?: 1; @endphp
    @foreach ($sorted as $k => $v)
        <tr>
            <td style="font-family:monospace;">{{ $k }}</td>
            <td>{{ number_format($v, 4) }}</td>
            <td><div style="height:8px; background:#e5e7eb; border-radius:4px;"><div style="height:8px; width:{{ ($v / $max) * 100 }}%; background:#2540ed; border-radius:4px;"></div></div></td>
        </tr>
    @endforeach
</table>

<h3>6. Catatan Metodologis</h3>
<p style="font-size:10px;">
    Analisis dilakukan dengan algoritma K-Means clustering (MacQueen, 1967) menggunakan inisialisasi <em>k-means++</em> (Arthur &amp; Vassilvitskii, 2007). Variabel kategorikal di-encode dengan One-Hot Encoding; variabel numerik di-standarisasi sebelum eksekusi. Validitas internal cluster dinilai dengan tiga indikator: Silhouette Coefficient (Rousseeuw, 1987), Davies–Bouldin Index (Davies &amp; Bouldin, 1979), dan Calinski–Harabasz Index (Caliński &amp; Harabasz, 1974). Reduksi dimensi untuk visualisasi menggunakan Principal Component Analysis (PCA) ke 2 komponen utama. Random state {{ $run->random_state }} digunakan agar hasil dapat direproduksi.
</p>

<div class="footer">
    SIANCEK · Sistem Analisis Klasterisasi Cybercrime · Halaman <span class="page-number"></span>
</div>

</body>
</html>
