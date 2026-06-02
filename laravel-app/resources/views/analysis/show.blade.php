@extends('layouts.app')
@section('title', $run->nama)
@section('page-title', $run->nama)
@section('page-subtitle', 'Hasil eksekusi K-Means clustering — '.$run->created_at->translatedFormat('d F Y, H:i'))

@section('content')
@php
    $clusterColors = ['#3a5cff','#06b6d4','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#14b8a6','#f97316','#6366f1','#84cc16','#a855f7'];
@endphp

@if ($run->status !== \App\Models\ClusteringRun::STATUS_SUKSES)
    <div class="bg-rose-50 border border-rose-200 rounded-2xl p-6 text-rose-700">
        <div class="font-semibold mb-1">Analisis tidak berhasil</div>
        <div class="text-sm">{{ $run->error_message ?? 'Status: '.$run->status }}</div>
    </div>
@else

{{-- =================== METRICS =================== --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-5">
    @php
        $cards = [
            ['label' => 'Jumlah Cluster (K)', 'value' => $run->n_clusters, 'sub' => 'mode '.$run->mode, 'color' => 'from-brand-600 to-brand-800'],
            ['label' => 'Silhouette Coefficient', 'value' => number_format($run->silhouette, 3), 'sub' => $run->qualityLabel(), 'color' => 'from-cyan-500 to-blue-600'],
            ['label' => 'Davies–Bouldin Index', 'value' => number_format($run->davies_bouldin, 3), 'sub' => '↓ lebih baik', 'color' => 'from-amber-500 to-orange-600'],
            ['label' => 'Calinski–Harabasz', 'value' => number_format($run->calinski_harabasz, 1), 'sub' => '↑ lebih baik', 'color' => 'from-violet-500 to-purple-600'],
            ['label' => 'Iterasi · Inertia', 'value' => $run->iterations.'×', 'sub' => 'WCSS '.number_format($run->inertia, 2), 'color' => 'from-rose-500 to-pink-600'],
        ];
    @endphp
    @foreach ($cards as $c)
        <div class="metric-card bg-white rounded-2xl p-5 shadow-soft border border-slate-200/60">
            <div class="text-[11px] uppercase tracking-wider text-slate-500 font-medium">{{ $c['label'] }}</div>
            <div class="mt-2 font-display text-2xl font-bold text-slate-800">{{ $c['value'] }}</div>
            <div class="mt-1 text-xs">
                <span class="inline-block px-2 py-0.5 rounded-full text-white text-[10px] bg-gradient-to-r {{ $c['color'] }}">{{ $c['sub'] }}</span>
            </div>
        </div>
    @endforeach
</div>

{{-- =================== AKSI =================== --}}
<div class="bg-white rounded-2xl shadow-soft border border-slate-200/60 px-5 py-3 mb-5 flex flex-wrap items-center justify-between gap-2">
    <div class="text-sm text-slate-600">
        Dataset: <span class="font-medium text-slate-800">{{ number_format($run->jumlah_data) }} baris</span> ·
        Fitur Numerik: <span class="font-medium text-slate-800">{{ count($run->fitur_numerik) }}</span> ·
        Fitur Kategorikal: <span class="font-medium text-slate-800">{{ count($run->fitur_kategorikal) }}</span> ·
        Random State: <span class="font-mono">{{ $run->random_state }}</span>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('reports.pdf', $run) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm rounded-lg border border-slate-200 hover:bg-slate-50">
            <svg class="w-4 h-4 text-rose-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Unduh PDF
        </a>
        <a href="{{ route('reports.excel', $run) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm rounded-lg border border-slate-200 hover:bg-slate-50">
            <svg class="w-4 h-4 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12V4m0 8l-3-3m3 3l3-3"/></svg>
            Unduh Excel
        </a>
    </div>
</div>

{{-- =================== VISUALISASI =================== --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-soft border border-slate-200/60 p-5">
        <div class="mb-4">
            <h3 class="font-display font-semibold text-slate-800">Visualisasi Cluster (PCA 2D)</h3>
            <p class="text-xs text-slate-500">Tiap titik = 1 laporan; warna = cluster yang dihasilkan</p>
        </div>
        <div class="relative h-96"><canvas id="scatterChart"></canvas></div>
    </div>

    <div class="bg-white rounded-2xl shadow-soft border border-slate-200/60 p-5">
        <div class="mb-4">
            <h3 class="font-display font-semibold text-slate-800">Distribusi Anggota</h3>
            <p class="text-xs text-slate-500">Jumlah laporan per cluster</p>
        </div>
        <div class="relative h-96"><canvas id="distChart"></canvas></div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
    <div class="bg-white rounded-2xl shadow-soft border border-slate-200/60 p-5">
        <div class="mb-4">
            <h3 class="font-display font-semibold text-slate-800">Feature Importance</h3>
            <p class="text-xs text-slate-500">Kontribusi tiap fitur terhadap pemisahan cluster (variansi inter-cluster)</p>
        </div>
        <div class="relative h-72"><canvas id="importanceChart"></canvas></div>
    </div>

    <div class="bg-white rounded-2xl shadow-soft border border-slate-200/60 p-5">
        <div class="mb-4">
            <h3 class="font-display font-semibold text-slate-800">Interpretasi Metrik</h3>
            <p class="text-xs text-slate-500">Ringkasan kualitas hasil clustering</p>
        </div>
        <div class="space-y-4">
            <div>
                <div class="flex justify-between text-sm mb-1"><span class="text-slate-700">Silhouette</span><span class="font-mono">{{ number_format($run->silhouette, 3) }} / 1.000</span></div>
                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500 rounded-full" style="width: {{ min(100, max(0, $run->silhouette * 100)) }}%"></div>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    @if($run->silhouette >= 0.7) Struktur cluster <strong>sangat jelas</strong>, anggota tiap cluster cocok dengan kelompoknya.
                    @elseif($run->silhouette >= 0.5) Struktur cluster <strong>baik</strong>.
                    @elseif($run->silhouette >= 0.25) Struktur cluster <strong>cukup</strong>, pertimbangkan menambah fitur atau mengubah K.
                    @else Struktur cluster <strong>lemah</strong>; tinjau ulang fitur dan K.
                    @endif
                </p>
            </div>
            <div>
                <div class="flex justify-between text-sm mb-1"><span class="text-slate-700">Davies–Bouldin</span><span class="font-mono">{{ number_format($run->davies_bouldin, 3) }}</span></div>
                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-amber-500 rounded-full" style="width: {{ min(100, max(0, 100 - $run->davies_bouldin * 25)) }}%"></div>
                </div>
                <p class="text-xs text-slate-500 mt-1">Semakin kecil semakin baik. Nilai &lt; 1 umumnya menunjukkan pemisahan yang baik.</p>
            </div>
            <div>
                <div class="flex justify-between text-sm mb-1"><span class="text-slate-700">Calinski–Harabasz</span><span class="font-mono">{{ number_format($run->calinski_harabasz, 1) }}</span></div>
                <p class="text-xs text-slate-500">Rasio variansi antar-cluster vs dalam-cluster. Semakin besar semakin baik.</p>
            </div>
        </div>
    </div>
</div>

{{-- =================== PROFIL CLUSTER =================== --}}
<div class="bg-white rounded-2xl shadow-soft border border-slate-200/60 mb-5">
    <div class="px-5 py-4 border-b border-slate-200/60">
        <h3 class="font-display font-semibold text-slate-800">Profil Tiap Cluster</h3>
        <p class="text-xs text-slate-500">Karakteristik dominan masing-masing kelompok (hasil interpretasi otomatis)</p>
    </div>
    <div class="p-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach ($profiles as $p)
            @php $color = $clusterColors[$p['cluster'] % count($clusterColors)]; @endphp
            <div class="rounded-xl border border-slate-200/60 overflow-hidden">
                <div class="px-4 py-3 flex items-center justify-between" style="background:linear-gradient(135deg, {{ $color }}20, {{ $color }}10); border-bottom: 1px solid {{ $color }}30;">
                    <div class="flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold" style="background:{{ $color }}">{{ $p['cluster'] }}</span>
                        <div>
                            <div class="font-display font-semibold text-slate-800">Cluster {{ $p['cluster'] }}</div>
                            <div class="text-[11px] text-slate-500">{{ $p['size'] }} anggota · {{ number_format($p['proportion'] * 100, 1) }}%</div>
                        </div>
                    </div>
                </div>
                <div class="p-4 text-sm text-slate-700">
                    <p class="leading-relaxed">{{ $p['summary'] }}</p>
                    @if (!empty($p['dominant_categorical']))
                        <div class="mt-3 pt-3 border-t border-slate-100">
                            <div class="text-[11px] uppercase tracking-wider text-slate-500 mb-1.5">Kategori Dominan</div>
                            <dl class="space-y-1">
                                @foreach ($p['dominant_categorical'] as $k => $v)
                                    <div class="flex justify-between text-xs">
                                        <dt class="text-slate-500 font-mono">{{ $k }}</dt>
                                        <dd class="text-slate-800 font-medium">{{ $v }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif
                    @if (!empty($p['centroid']))
                        <div class="mt-3 pt-3 border-t border-slate-100">
                            <div class="text-[11px] uppercase tracking-wider text-slate-500 mb-1.5">Rata-rata Numerik</div>
                            <dl class="space-y-1">
                                @foreach ($p['centroid'] as $k => $v)
                                    <div class="flex justify-between text-xs">
                                        <dt class="text-slate-500 font-mono">{{ $k }}</dt>
                                        <dd class="text-slate-800 font-mono">{{ number_format($v, 2) }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
    const projection = @json($projection);
    const distribution = @json($distribution);
    const importance = @json($importance);
    const palette = @json($clusterColors);
    Chart.defaults.font.family = 'Inter, ui-sans-serif, system-ui';
    Chart.defaults.color = '#64748b';

    // ============ SCATTER PCA ============
    const grouped = {};
    projection.forEach(p => {
        if (!grouped[p.cluster]) grouped[p.cluster] = [];
        grouped[p.cluster].push({ x: p.x, y: p.y });
    });
    const datasets = Object.keys(grouped).sort((a,b) => +a - +b).map(c => ({
        label: 'Cluster ' + c,
        data: grouped[c],
        backgroundColor: palette[+c % palette.length] + 'BB',
        borderColor: palette[+c % palette.length],
        pointRadius: 3.5, pointHoverRadius: 6, borderWidth: 1,
    }));
    new Chart(document.getElementById('scatterChart'), {
        type: 'scatter',
        data: { datasets },
        options: { responsive:true, maintainAspectRatio:false, scales:{ x:{ title:{display:true, text:'Komponen Utama 1 (PC1)'}, grid:{color:'#f1f5f9'}}, y:{ title:{display:true, text:'Komponen Utama 2 (PC2)'}, grid:{color:'#f1f5f9'}}}, plugins:{ legend:{position:'top', labels:{boxWidth:10, font:{size:11}}}}}
    });

    // ============ DISTRIBUSI ============
    const distLabels = Object.keys(distribution).map(c => 'C' + c);
    new Chart(document.getElementById('distChart'), {
        type: 'doughnut',
        data: {
            labels: distLabels,
            datasets: [{
                data: Object.values(distribution),
                backgroundColor: distLabels.map((_, i) => palette[i % palette.length]),
                borderColor: '#fff', borderWidth: 2,
            }]
        },
        options: { responsive:true, maintainAspectRatio:false, cutout:'65%', plugins:{ legend:{position:'bottom', labels:{boxWidth:10, font:{size:11}}}}}
    });

    // ============ FEATURE IMPORTANCE ============
    const impEntries = Object.entries(importance).sort((a,b) => b[1] - a[1]).slice(0, 15);
    new Chart(document.getElementById('importanceChart'), {
        type: 'bar',
        data: {
            labels: impEntries.map(([k]) => k),
            datasets: [{
                label: 'Importance',
                data: impEntries.map(([,v]) => v),
                backgroundColor: (ctx) => {
                    const c = ctx.chart.ctx;
                    const g = c.createLinearGradient(0, 0, 400, 0);
                    g.addColorStop(0, '#06b6d4'); g.addColorStop(1, '#2540ed');
                    return g;
                },
                borderRadius: 6, barThickness: 14,
            }]
        },
        options: { indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ x:{ grid:{color:'#f1f5f9'}}, y:{ grid:{display:false}}} }
    });
</script>
@endpush

@endif
@endsection
