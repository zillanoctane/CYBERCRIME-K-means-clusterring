@extends('layouts.app')
@section('title', 'Analisis Baru')
@section('page-title', 'Buat Analisis Cluster Baru')
@section('page-subtitle', 'Konfigurasi parameter K-Means, pratinjau Elbow, lalu eksekusi')

@php
    // Default fitur diekstrak ke variabel agar tidak inline-array di dalam
    // direktif @checked/@json (Blade tidak menerima `[...]` di dalam argumen
    // direktif berkurung — ParseError "Unclosed '[' does not match ')'").
    $defaultNumeric = ['estimasi_kerugian', 'jumlah_korban', 'keparahan_score'];
    $defaultCategorical = ['jenis_kejahatan', 'provinsi', 'tingkat_keparahan'];
    $selectedNumeric = old('fitur_numerik', $defaultNumeric);
    $selectedCategorical = old('fitur_kategorikal', $defaultCategorical);
@endphp

@section('content')
<div x-data="analysisForm()" class="space-y-5">

    <form method="POST" action="{{ route('analysis.store') }}" @submit="ensureK()" class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        @csrf

        {{-- =================== LEFT: Konfigurasi =================== --}}
        <div class="lg:col-span-2 space-y-5">
            {{-- Step 1 --}}
            <section class="bg-white rounded-2xl shadow-soft border border-slate-200/60 p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-7 h-7 rounded-full bg-brand-600 text-white text-xs font-bold flex items-center justify-center">1</div>
                    <div>
                        <h3 class="font-display font-semibold text-slate-800">Identitas Analisis</h3>
                        <p class="text-xs text-slate-500">Beri nama yang deskriptif agar mudah dirujuk di laporan</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-slate-700">Nama Analisis <span class="text-rose-500">*</span></label>
                        <input type="text" name="nama" value="{{ old('nama') }}" required class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm"
                               placeholder="Contoh: Klasterisasi Cybercrime Indonesia 2023" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Random State</label>
                        <input type="number" name="random_state" value="{{ old('random_state', 42) }}" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm font-mono" />
                    </div>
                </div>
                <div class="mt-3">
                    <label class="text-sm font-medium text-slate-700">Deskripsi / Tujuan</label>
                    <textarea name="deskripsi" rows="2" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm" placeholder="Tujuan penelitian atau hipotesis...">{{ old('deskripsi') }}</textarea>
                </div>
            </section>

            {{-- Step 2: Fitur --}}
            <section class="bg-white rounded-2xl shadow-soft border border-slate-200/60 p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-7 h-7 rounded-full bg-brand-600 text-white text-xs font-bold flex items-center justify-center">2</div>
                    <div>
                        <h3 class="font-display font-semibold text-slate-800">Pemilihan Fitur</h3>
                        <p class="text-xs text-slate-500">Pilih fitur numerik &amp; kategorikal yang relevan untuk clustering</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Numerik</h4>
                        <div class="space-y-1.5">
                            @foreach ($allowedNumeric as $f)
                                <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-slate-50 px-2 py-1.5 rounded">
                                    <input type="checkbox" name="fitur_numerik[]" value="{{ $f }}" x-model="numeric" class="rounded text-brand-600 focus:ring-brand-500" @checked(in_array($f, $selectedNumeric))>
                                    <span class="font-mono">{{ $f }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Kategorikal</h4>
                        <div class="space-y-1.5 max-h-64 overflow-y-auto scrollbar-thin pr-2">
                            @foreach ($allowedCategorical as $f)
                                <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-slate-50 px-2 py-1.5 rounded">
                                    <input type="checkbox" name="fitur_kategorikal[]" value="{{ $f }}" x-model="categorical" class="rounded text-brand-600 focus:ring-brand-500" @checked(in_array($f, $selectedCategorical))>
                                    <span class="font-mono">{{ $f }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Scaler</label>
                        <select name="scaler" x-model="scaler" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm">
                            <option value="standard">StandardScaler (Z-score)</option>
                            <option value="minmax">MinMaxScaler [0,1]</option>
                            <option value="robust">RobustScaler (median/IQR)</option>
                        </select>
                    </div>
                </div>
            </section>

            {{-- Step 3: Filter --}}
            <section class="bg-white rounded-2xl shadow-soft border border-slate-200/60 p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-7 h-7 rounded-full bg-brand-600 text-white text-xs font-bold flex items-center justify-center">3</div>
                    <div class="flex-1">
                        <h3 class="font-display font-semibold text-slate-800">Filter Subset Data <span class="text-xs font-normal text-slate-400">(opsional)</span></h3>
                        <p class="text-xs text-slate-500">Batasi data yang akan dianalisis</p>
                    </div>
                    {{-- Live matched-row counter: beri tahu pengguna ukuran subset sebelum klik Elbow --}}
                    <div class="text-right shrink-0">
                        <span x-show="countLoading" class="text-xs text-slate-400">menghitung…</span>
                        <div x-show="!countLoading && matchedCount !== null" x-cloak
                             class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                             :class="matchedCount >= minRequired ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'">
                            <span x-text="matchedCount >= minRequired ? '✓' : '!'"></span>
                            <span><span x-text="matchedCount"></span> baris cocok</span>
                        </div>
                        <p x-show="!countLoading && matchedCount !== null && matchedCount < minRequired" x-cloak
                           class="text-[11px] text-rose-600 mt-1 max-w-[14rem]">
                            Minimal <span x-text="minRequired"></span> baris. Perluas filter atau kurangi K Min.
                        </p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-sm font-medium text-slate-700">Mulai</label>
                            <input type="date" name="filter[tanggal_mulai]" x-model="filter.tanggal_mulai" value="{{ old('filter.tanggal_mulai') }}" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700">Selesai</label>
                            <input type="date" name="filter[tanggal_selesai]" x-model="filter.tanggal_selesai" value="{{ old('filter.tanggal_selesai') }}" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm" />
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Jenis Kejahatan</label>
                        <select name="filter[jenis_kejahatan][]" multiple x-model="filter.jenis_kejahatan" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm h-24">
                            @foreach ($jenisList as $j)
                                <option value="{{ $j }}">{{ $j }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-slate-700">Provinsi</label>
                        <select name="filter[provinsi][]" multiple x-model="filter.provinsi" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm h-24">
                            @foreach ($provinsiList as $p)
                                <option value="{{ $p }}">{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            {{-- Step 4: K --}}
            <section class="bg-white rounded-2xl shadow-soft border border-slate-200/60 p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded-full bg-brand-600 text-white text-xs font-bold flex items-center justify-center">4</div>
                        <div>
                            <h3 class="font-display font-semibold text-slate-800">Penentuan Jumlah Cluster (K)</h3>
                            <p class="text-xs text-slate-500">Pratinjau Elbow / Silhouette terlebih dahulu agar K yang dipilih optimal</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Mode Penentuan K</label>
                        <select name="mode" x-model="mode" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm">
                            <option value="manual">Manual</option>
                            <option value="auto">Otomatis (dari Elbow)</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Jumlah Cluster (K) <span class="text-rose-500">*</span></label>
                        <input type="number" name="n_clusters" min="2" max="20" x-model.number="nClusters" required class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm font-mono" />
                        <p class="text-[11px] text-slate-500 mt-1">Direkomendasikan: <span x-text="recommendedK ?? '—'" class="font-mono font-semibold text-brand-600"></span></p>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-sm font-medium text-slate-700">K Min</label>
                            <input type="number" min="2" max="20" x-model.number="kMin" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm font-mono" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700">K Max</label>
                            <input type="number" min="2" max="20" x-model.number="kMax" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm font-mono" />
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button type="button" @click="runElbow()" :disabled="loading" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-900 text-white text-sm hover:bg-slate-800 disabled:opacity-60">
                        <svg x-show="!loading" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l11 7-11 6zM9 6L4 9v8l5 3"/></svg>
                        <svg x-show="loading" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.5" class="opacity-25"/><path d="M12 2a10 10 0 0110 10" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
                        <span x-text="loading ? 'Menghitung Elbow…' : 'Pratinjau Elbow'"></span>
                    </button>
                    <span class="text-xs text-slate-500" x-show="recommendation" x-text="recommendation"></span>
                    <span class="text-xs text-rose-600" x-show="elbowError" x-text="elbowError"></span>
                </div>

                <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4" x-show="elbowPoints.length">
                    <div class="bg-slate-50/60 rounded-xl p-4 border border-slate-200/60">
                        <h4 class="text-sm font-semibold text-slate-800 mb-2">Kurva Elbow (WCSS)</h4>
                        <div class="relative h-60"><canvas id="elbowChart"></canvas></div>
                    </div>
                    <div class="bg-slate-50/60 rounded-xl p-4 border border-slate-200/60">
                        <h4 class="text-sm font-semibold text-slate-800 mb-2">Silhouette &amp; Davies–Bouldin</h4>
                        <div class="relative h-60"><canvas id="silDbChart"></canvas></div>
                    </div>
                </div>
            </section>
        </div>

        {{-- =================== RIGHT: Summary =================== --}}
        <aside class="space-y-5">
            <div class="bg-gradient-to-br from-brand-900 to-brand-700 text-white rounded-2xl p-5 shadow-xl">
                <div class="text-xs uppercase tracking-widest opacity-70 mb-1">Dataset</div>
                <div class="font-display text-3xl font-bold">{{ number_format($totalData) }}</div>
                <div class="text-sm opacity-80 mt-0.5">total laporan tersedia</div>
                <div class="mt-4 text-xs leading-relaxed opacity-80">
                    Algoritma <strong>K-Means++</strong> dengan validasi: <strong>Silhouette</strong>, <strong>Davies-Bouldin</strong>, <strong>Calinski-Harabasz</strong>. Visualisasi cluster diproyeksikan ke bidang 2D melalui <strong>PCA</strong>.
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-soft border border-slate-200/60 p-5">
                <h4 class="font-display font-semibold text-slate-800 mb-3">Ringkasan Konfigurasi</h4>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Fitur Numerik</dt><dd class="font-mono text-slate-800" x-text="numeric.length"></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Fitur Kategorikal</dt><dd class="font-mono text-slate-800" x-text="categorical.length"></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Scaler</dt><dd class="font-mono text-slate-800" x-text="scaler"></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">K</dt><dd class="font-mono text-slate-800" x-text="nClusters"></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Mode</dt><dd class="font-mono text-slate-800" x-text="mode"></dd></div>
                </dl>

                <button type="submit" class="mt-5 w-full py-2.5 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold shadow-lg shadow-brand-600/20 transition">
                    Jalankan Clustering
                </button>
                <a href="{{ route('analysis.index') }}" class="block text-center mt-2 text-sm text-slate-500 hover:text-slate-700">Batal</a>
            </div>

            <div class="bg-white rounded-2xl shadow-soft border border-slate-200/60 p-5">
                <h4 class="font-display font-semibold text-slate-800 mb-2 text-sm">Catatan Metodologis</h4>
                <ul class="text-xs text-slate-600 space-y-1.5 list-disc list-inside">
                    <li>Minimal 5 baris data per cluster untuk validitas statistik.</li>
                    <li>Random state memastikan hasil dapat direproduksi.</li>
                    <li>Encoding One-Hot untuk variabel kategorikal.</li>
                    <li>K optimal: kompromi Elbow (WCSS), Silhouette, dan DB Index.</li>
                </ul>
            </div>
        </aside>
    </form>
</div>

@push('scripts')
<script>
    function analysisForm() {
        return {
            numeric: @json($selectedNumeric),
            categorical: @json($selectedCategorical),
            scaler: @json(old('scaler', 'standard')),
            mode: @json(old('mode', 'manual')),
            nClusters: parseInt(@json(old('n_clusters', 4))),
            kMin: 2, kMax: 10,
            filter: { tanggal_mulai: '', tanggal_selesai: '', jenis_kejahatan: [], provinsi: [] },
            elbowPoints: [],
            recommendedK: null,
            recommendation: '',
            elbowError: '',
            loading: false,
            elbowChart: null,
            silDbChart: null,
            matchedCount: null,
            minRequired: 10,
            countLoading: false,
            countTimer: null,

            init() {
                // Hitung baris saat filter atau K Min berubah (debounce agar tidak spam server)
                this.$watch('filter', () => this.scheduleCount(), { deep: true });
                this.$watch('kMin', () => this.scheduleCount());
                this.refreshCount();
            },

            scheduleCount() {
                clearTimeout(this.countTimer);
                this.countTimer = setTimeout(() => this.refreshCount(), 400);
            },

            async refreshCount() {
                this.countLoading = true;
                try {
                    const res = await fetch(@json(route('analysis.preview-count', [], false)), {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ k_min: this.kMin, filter: this.filter }),
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    this.matchedCount = data.count;
                    this.minRequired = data.min_required;
                } catch (e) {
                    // diam-diam: counter hanya bantuan, jangan ganggu alur utama
                } finally {
                    this.countLoading = false;
                }
            },

            ensureK() {
                if (!this.nClusters || this.nClusters < 2) {
                    this.nClusters = this.recommendedK || 3;
                }
            },

            async runElbow() {
                this.loading = true;
                this.elbowError = '';
                this.elbowPoints = [];
                try {
                    const res = await fetch(@json(route('analysis.elbow', [], false)), {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            fitur_numerik: this.numeric,
                            fitur_kategorikal: this.categorical,
                            scaler: this.scaler,
                            k_min: this.kMin,
                            k_max: this.kMax,
                            filter: this.filter,
                        }),
                    });
                    if (res.status === 419) {
                        this.elbowError = 'Sesi kedaluwarsa (CSRF). Muat ulang halaman ini (Ctrl+F5) lalu coba lagi.';
                        return;
                    }
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        this.elbowError = data.message || 'Gagal menghitung Elbow.';
                        return;
                    }
                    this.elbowPoints = data.data.points;
                    this.recommendedK = data.data.recommended_k;
                    this.recommendation = data.data.recommendation_reason;
                    this.nClusters = this.recommendedK;
                    this.$nextTick(() => this.renderCharts());
                } catch (e) {
                    this.elbowError = 'Tidak dapat terhubung ke service. ' + e.message;
                } finally {
                    this.loading = false;
                }
            },

            renderCharts() {
                const labels = this.elbowPoints.map(p => p.k);
                if (this.elbowChart) this.elbowChart.destroy();
                this.elbowChart = new Chart(document.getElementById('elbowChart'), {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label: 'WCSS',
                            data: this.elbowPoints.map(p => p.wcss),
                            borderColor: '#2540ed', backgroundColor: 'rgba(37,64,237,.15)',
                            fill: true, tension: 0.3, borderWidth: 2,
                            pointRadius: 5, pointBackgroundColor: (ctx) => ctx.parsed.x === this.recommendedK ? '#ef4444' : '#2540ed',
                            pointBorderColor: '#fff', pointBorderWidth: 2,
                        }]
                    },
                    options: { responsive:true, maintainAspectRatio:false, scales:{ y:{ beginAtZero:false, grid:{color:'#f1f5f9'}}, x:{ grid:{display:false}, title:{display:true, text:'K'}}}, plugins:{legend:{display:false}} }
                });

                if (this.silDbChart) this.silDbChart.destroy();
                this.silDbChart = new Chart(document.getElementById('silDbChart'), {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            { label: 'Silhouette (↑)', data: this.elbowPoints.map(p => p.silhouette), borderColor: '#10b981', backgroundColor:'transparent', yAxisID:'y1', tension:.3, borderWidth:2, pointRadius:4 },
                            { label: 'Davies-Bouldin (↓)', data: this.elbowPoints.map(p => p.davies_bouldin), borderColor: '#f59e0b', backgroundColor:'transparent', yAxisID:'y2', tension:.3, borderWidth:2, pointRadius:4 },
                        ]
                    },
                    options: {
                        responsive:true, maintainAspectRatio:false,
                        scales:{
                            x:{ grid:{display:false}, title:{display:true, text:'K'}},
                            y1:{ position:'left', grid:{color:'#f1f5f9'}, title:{display:true, text:'Silhouette'}},
                            y2:{ position:'right', grid:{display:false}, title:{display:true, text:'DB'}}
                        }
                    }
                });
            }
        };
    }
</script>
@endpush
@endsection
