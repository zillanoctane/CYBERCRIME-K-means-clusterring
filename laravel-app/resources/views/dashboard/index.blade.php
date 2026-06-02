@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard Eksekutif')
@section('page-subtitle', 'Ringkasan statistik dan tren kejahatan siber')

@section('content')
@php
    function rp(int $n): string {
        if ($n >= 1_000_000_000) return 'Rp '.number_format($n/1_000_000_000, 1, ',', '.').' M';
        if ($n >= 1_000_000) return 'Rp '.number_format($n/1_000_000, 1, ',', '.').' Jt';
        if ($n >= 1_000) return 'Rp '.number_format($n/1_000, 0, ',', '.').' Rb';
        return 'Rp '.number_format($n, 0, ',', '.');
    }
@endphp

{{-- ============================== METRICS GRID ============================== --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    @php
        $metrics = [
            ['label' => 'Total Laporan',           'value' => number_format($stats['total']), 'delta' => null, 'icon' => 'M4 7h16M4 11h16M4 15h10', 'gradient' => 'from-brand-500 to-brand-700'],
            ['label' => 'Laporan Tahun Ini',       'value' => number_format($stats['tahunIni']), 'delta' => null, 'icon' => 'M3 3v18h18M7 14l4-4 4 4 6-6', 'gradient' => 'from-cyan-500 to-blue-600'],
            ['label' => 'Estimasi Kerugian YTD',   'value' => rp($stats['kerugianYtd']), 'delta' => null, 'icon' => 'M12 8c-1.657 0-3 1.343-3 3 0 1.657 1.343 3 3 3s3 1.343 3 3-1.343 3-3 3m0-12V5m0 14v2m0-2c1.657 0 3-1.343 3-3', 'gradient' => 'from-rose-500 to-pink-600'],
            ['label' => 'Analisis Berhasil',       'value' => number_format($stats['jumlahRuns']), 'delta' => null, 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'gradient' => 'from-emerald-500 to-green-600'],
        ];
    @endphp
    @foreach ($metrics as $m)
        <div class="metric-card bg-white rounded-2xl p-5 shadow-soft border border-slate-200/60">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-xs uppercase tracking-wider text-slate-500 font-medium">{{ $m['label'] }}</div>
                    <div class="font-display text-2xl xl:text-3xl font-bold text-slate-800 mt-1">{{ $m['value'] }}</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br {{ $m['gradient'] }} flex items-center justify-center text-white shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $m['icon'] }}"/>
                    </svg>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ============================== CHARTS ROW 1 ============================== --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <div class="lg:col-span-2 bg-white rounded-2xl p-5 shadow-soft border border-slate-200/60">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-display font-semibold text-slate-800">Tren Bulanan (24 Bulan Terakhir)</h3>
                <p class="text-xs text-slate-500">Jumlah laporan per bulan</p>
            </div>
            <span class="inline-flex items-center gap-1.5 text-xs text-brand-600 bg-brand-50 px-2.5 py-1 rounded-full font-medium">
                <span class="w-1.5 h-1.5 rounded-full bg-brand-500"></span>Real-time
            </span>
        </div>
        <div class="relative h-72"><canvas id="chartTrendBulanan"></canvas></div>
    </div>

    <div class="bg-white rounded-2xl p-5 shadow-soft border border-slate-200/60">
        <div class="mb-4">
            <h3 class="font-display font-semibold text-slate-800">Distribusi Jenis</h3>
            <p class="text-xs text-slate-500">10 jenis kejahatan tertinggi</p>
        </div>
        <div class="relative h-72"><canvas id="chartJenis"></canvas></div>
    </div>
</div>

{{-- ============================== CHARTS ROW 2 ============================== --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 shadow-soft border border-slate-200/60">
        <div class="mb-4">
            <h3 class="font-display font-semibold text-slate-800">Tren Tahunan</h3>
            <p class="text-xs text-slate-500">Jumlah laporan &amp; kerugian per tahun</p>
        </div>
        <div class="relative h-64"><canvas id="chartTrendTahunan"></canvas></div>
    </div>

    <div class="lg:col-span-2 bg-white rounded-2xl p-5 shadow-soft border border-slate-200/60">
        <div class="mb-4">
            <h3 class="font-display font-semibold text-slate-800">Top 10 Provinsi</h3>
            <p class="text-xs text-slate-500">Sebaran geografis laporan</p>
        </div>
        <div class="relative h-64"><canvas id="chartProvinsi"></canvas></div>
    </div>
</div>

{{-- ============================== LATEST RUNS ============================== --}}
<div class="bg-white rounded-2xl shadow-soft border border-slate-200/60 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200/60 flex items-center justify-between">
        <div>
            <h3 class="font-display font-semibold text-slate-800">Analisis Terbaru</h3>
            <p class="text-xs text-slate-500">5 eksekusi clustering terkini</p>
        </div>
        <a href="{{ route('analysis.index') }}" class="text-sm text-brand-600 hover:text-brand-700 font-medium">Lihat semua →</a>
    </div>
    @if ($latestRuns->isEmpty())
        <div class="px-5 py-10 text-center text-sm text-slate-500">Belum ada analisis yang dijalankan.
            @if (auth()->user()->canAnalyze())
                <a href="{{ route('analysis.create') }}" class="text-brand-600 hover:text-brand-700 font-medium">Mulai analisis pertama</a>.
            @endif
        </div>
    @else
        <div class="overflow-x-auto scrollbar-thin">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="text-left px-5 py-3 font-medium">Nama</th>
                        <th class="text-left px-5 py-3 font-medium">K</th>
                        <th class="text-left px-5 py-3 font-medium">Data</th>
                        <th class="text-left px-5 py-3 font-medium">Silhouette</th>
                        <th class="text-left px-5 py-3 font-medium">Kualitas</th>
                        <th class="text-left px-5 py-3 font-medium">Tanggal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @foreach ($latestRuns as $run)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-5 py-3 font-medium text-slate-800">{{ $run->nama }}</td>
                        <td class="px-5 py-3">{{ $run->n_clusters }}</td>
                        <td class="px-5 py-3">{{ number_format($run->jumlah_data) }}</td>
                        <td class="px-5 py-3 font-mono">{{ number_format($run->silhouette ?? 0, 3) }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-block px-2 py-0.5 text-xs rounded-full
                                @if(($run->silhouette ?? 0) >= 0.5) bg-ok-500/15 text-ok-600
                                @elseif(($run->silhouette ?? 0) >= 0.25) bg-warn-500/15 text-warn-600
                                @else bg-danger-500/15 text-danger-600 @endif">
                                {{ $run->qualityLabel() }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-500">{{ $run->created_at->translatedFormat('d M Y H:i') }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('analysis.show', $run) }}" class="text-brand-600 hover:text-brand-700 text-sm font-medium">Lihat →</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@push('scripts')
<script>
    const palette = ['#3a5cff','#06b6d4','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#14b8a6','#f97316','#6366f1'];
    Chart.defaults.font.family = 'Inter, ui-sans-serif, system-ui';
    Chart.defaults.color = '#64748b';

    // Tren bulanan
    new Chart(document.getElementById('chartTrendBulanan'), {
        type: 'line',
        data: {
            labels: @json(collect($stats['trenBulanan'])->pluck('periode')),
            datasets: [{
                label: 'Laporan',
                data: @json(collect($stats['trenBulanan'])->pluck('total')),
                fill: true,
                tension: 0.35,
                borderColor: '#2540ed',
                backgroundColor: (ctx) => {
                    const c = ctx.chart.ctx;
                    const g = c.createLinearGradient(0, 0, 0, 280);
                    g.addColorStop(0, 'rgba(37,64,237,.35)');
                    g.addColorStop(1, 'rgba(37,64,237,0)');
                    return g;
                },
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 5,
            }]
        },
        options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false}}, scales:{ x:{ grid:{display:false}}, y:{ grid:{color:'#f1f5f9'}, beginAtZero:true}}}
    });

    // Distribusi jenis (doughnut)
    new Chart(document.getElementById('chartJenis'), {
        type: 'doughnut',
        data: {
            labels: @json(collect($stats['perJenis'])->pluck('label')),
            datasets: [{
                data: @json(collect($stats['perJenis'])->pluck('total')),
                backgroundColor: palette,
                borderColor: '#fff', borderWidth: 2
            }]
        },
        options: { responsive:true, maintainAspectRatio:false, cutout:'62%', plugins:{ legend:{ position:'right', labels:{ boxWidth:10, font:{size:11}}}}}
    });

    // Tren tahunan
    new Chart(document.getElementById('chartTrendTahunan'), {
        type: 'bar',
        data: {
            labels: @json(collect($stats['trenTahunan'])->pluck('tahun')),
            datasets: [{
                label: 'Laporan',
                data: @json(collect($stats['trenTahunan'])->pluck('total')),
                backgroundColor: '#3a5cff', borderRadius: 8, barThickness: 30,
            }]
        },
        options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ x:{grid:{display:false}}, y:{ grid:{color:'#f1f5f9'}, beginAtZero:true}}}
    });

    // Top provinsi (horizontal bar)
    new Chart(document.getElementById('chartProvinsi'), {
        type: 'bar',
        data: {
            labels: @json(collect($stats['perProvinsi'])->pluck('label')),
            datasets: [{
                label: 'Laporan',
                data: @json(collect($stats['perProvinsi'])->pluck('total')),
                backgroundColor: (ctx) => {
                    const c = ctx.chart.ctx;
                    const g = c.createLinearGradient(0, 0, 600, 0);
                    g.addColorStop(0, '#06b6d4'); g.addColorStop(1, '#2540ed');
                    return g;
                },
                borderRadius: 6, barThickness: 14,
            }]
        },
        options: { indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ x:{ grid:{color:'#f1f5f9'}, beginAtZero:true}, y:{ grid:{display:false}}}}
    });
</script>
@endpush
@endsection
