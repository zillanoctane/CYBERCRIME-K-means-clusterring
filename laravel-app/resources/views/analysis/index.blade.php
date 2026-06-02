@extends('layouts.app')
@section('title', 'Analisis Cluster')
@section('page-title', 'Analisis Cluster')
@section('page-subtitle', 'Riwayat eksekusi K-Means clustering')

@section('content')
<div class="bg-white rounded-2xl shadow-soft border border-slate-200/60">
    <div class="px-5 py-4 border-b border-slate-200/60 flex items-center justify-between">
        <div>
            <h3 class="font-display font-semibold text-slate-800">Daftar Analisis</h3>
            <p class="text-xs text-slate-500">Total {{ $runs->total() }} analisis tersimpan</p>
        </div>
        @if (auth()->user()->canAnalyze())
            <a href="{{ route('analysis.create') }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm rounded-lg bg-brand-600 text-white hover:bg-brand-700">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                Analisis Baru
            </a>
        @endif
    </div>

    <div class="overflow-x-auto scrollbar-thin">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="text-left px-5 py-3 font-medium">Nama</th>
                    <th class="text-left px-5 py-3 font-medium">K</th>
                    <th class="text-left px-5 py-3 font-medium">Mode</th>
                    <th class="text-left px-5 py-3 font-medium">Data</th>
                    <th class="text-left px-5 py-3 font-medium">Silhouette</th>
                    <th class="text-left px-5 py-3 font-medium">DB Index</th>
                    <th class="text-left px-5 py-3 font-medium">Status</th>
                    <th class="text-left px-5 py-3 font-medium">Dibuat</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($runs as $run)
                <tr class="hover:bg-slate-50/60">
                    <td class="px-5 py-3">
                        <div class="font-medium text-slate-800">{{ $run->nama }}</div>
                        <div class="text-xs text-slate-500">{{ $run->creator?->name ?? '—' }}</div>
                    </td>
                    <td class="px-5 py-3"><span class="font-mono">{{ $run->n_clusters }}</span></td>
                    <td class="px-5 py-3"><span class="px-2 py-0.5 text-xs rounded-full bg-slate-100 text-slate-700">{{ $run->mode }}</span></td>
                    <td class="px-5 py-3 text-slate-600">{{ number_format($run->jumlah_data) }}</td>
                    <td class="px-5 py-3 font-mono">{{ $run->silhouette !== null ? number_format($run->silhouette, 3) : '—' }}</td>
                    <td class="px-5 py-3 font-mono">{{ $run->davies_bouldin !== null ? number_format($run->davies_bouldin, 3) : '—' }}</td>
                    <td class="px-5 py-3">
                        @php
                            $b = ['sukses' => 'bg-emerald-100 text-emerald-700', 'gagal' => 'bg-rose-100 text-rose-700', 'draft' => 'bg-slate-100 text-slate-700'][$run->status] ?? 'bg-slate-100 text-slate-700';
                        @endphp
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $b }}">{{ ucfirst($run->status) }}</span>
                    </td>
                    <td class="px-5 py-3 text-slate-500">{{ $run->created_at->translatedFormat('d M Y H:i') }}</td>
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('analysis.show', $run) }}" class="text-brand-600 hover:text-brand-700 text-sm font-medium">Lihat →</a>
                        @if (auth()->user()->isAdmin())
                            <form method="POST" action="{{ route('analysis.destroy', $run) }}" class="inline" onsubmit="return confirm('Hapus analisis ini?');">
                                @csrf @method('DELETE')
                                <button class="text-danger-600 hover:text-danger-700 text-sm font-medium ml-3">Hapus</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-5 py-10 text-center text-sm text-slate-500">
                        Belum ada analisis.
                        @if (auth()->user()->canAnalyze())
                            <a href="{{ route('analysis.create') }}" class="text-brand-600 hover:text-brand-700 font-medium">Buat sekarang</a>.
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-5 py-3 border-t border-slate-200/60">
        {{ $runs->links() }}
    </div>
</div>
@endsection
