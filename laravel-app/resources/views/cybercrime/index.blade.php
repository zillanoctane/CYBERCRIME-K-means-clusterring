@extends('layouts.app')
@section('title', 'Data Cybercrime')
@section('page-title', 'Data Cybercrime')
@section('page-subtitle', 'Repositori laporan tindak pidana siber')

@section('content')
<div class="bg-white rounded-2xl shadow-soft border border-slate-200/60">
    {{-- Toolbar --}}
    <div class="px-5 py-4 border-b border-slate-200/60 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <form method="GET" class="flex flex-wrap gap-2 items-end">
            <div>
                <label class="block text-[11px] text-slate-500 mb-1">Pencarian</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="No. laporan, jenis, provinsi..."
                       class="w-56 text-sm rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500" />
            </div>
            <div>
                <label class="block text-[11px] text-slate-500 mb-1">Jenis</label>
                <select name="jenis" class="text-sm rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Semua</option>
                    @foreach ($jenisList as $j)
                        <option value="{{ $j }}" @selected(request('jenis') === $j)>{{ $j }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] text-slate-500 mb-1">Provinsi</label>
                <select name="provinsi" class="text-sm rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Semua</option>
                    @foreach ($provinsiList as $p)
                        <option value="{{ $p }}" @selected(request('provinsi') === $p)>{{ $p }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] text-slate-500 mb-1">Mulai</label>
                <input type="date" name="mulai" value="{{ request('mulai') }}" class="text-sm rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500" />
            </div>
            <div>
                <label class="block text-[11px] text-slate-500 mb-1">Selesai</label>
                <input type="date" name="selesai" value="{{ request('selesai') }}" class="text-sm rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500" />
            </div>
            <button class="px-4 py-2 text-sm rounded-lg bg-brand-600 text-white hover:bg-brand-700">Filter</button>
            @if (request()->anyFilled(['q','jenis','provinsi','mulai','selesai']))
                <a href="{{ route('cybercrime.index') }}" class="px-3 py-2 text-sm text-slate-500 hover:text-slate-700">Reset</a>
            @endif
        </form>

        @if (auth()->user()->canAnalyze())
            <div class="flex gap-2">
                <a href="{{ route('cybercrime.import-form') }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm rounded-lg border border-slate-200 hover:bg-slate-50">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12V4m0 0L8 8m4-4l4 4"/></svg>
                    Import CSV
                </a>
                <a href="{{ route('cybercrime.create') }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm rounded-lg bg-brand-600 text-white hover:bg-brand-700">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                    Tambah Laporan
                </a>
            </div>
        @endif
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto scrollbar-thin">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="text-left px-5 py-3 font-medium">No. Laporan</th>
                    <th class="text-left px-5 py-3 font-medium">Tanggal</th>
                    <th class="text-left px-5 py-3 font-medium">Jenis</th>
                    <th class="text-left px-5 py-3 font-medium">Modus</th>
                    <th class="text-left px-5 py-3 font-medium">Provinsi</th>
                    <th class="text-right px-5 py-3 font-medium">Kerugian</th>
                    <th class="text-left px-5 py-3 font-medium">Keparahan</th>
                    <th class="text-left px-5 py-3 font-medium">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($records as $r)
                <tr class="hover:bg-slate-50/60">
                    <td class="px-5 py-3 font-mono text-xs text-slate-600">{{ $r->nomor_laporan }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $r->tanggal_kejadian->translatedFormat('d M Y') }}</td>
                    <td class="px-5 py-3"><span class="font-medium text-slate-800">{{ $r->jenis_kejahatan }}</span><div class="text-xs text-slate-500">{{ $r->sub_jenis }}</div></td>
                    <td class="px-5 py-3 text-slate-600">{{ $r->modus_operandi }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $r->provinsi }}</td>
                    <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($r->estimasi_kerugian, 0, ',', '.') }}</td>
                    <td class="px-5 py-3">
                        @php
                            $badge = ['rendah' => 'bg-emerald-100 text-emerald-700', 'sedang' => 'bg-amber-100 text-amber-700', 'tinggi' => 'bg-orange-100 text-orange-700', 'kritis' => 'bg-rose-100 text-rose-700'][$r->tingkat_keparahan] ?? 'bg-slate-100 text-slate-700';
                        @endphp
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $badge }}">{{ ucfirst($r->tingkat_keparahan) }}</span>
                    </td>
                    <td class="px-5 py-3 text-xs text-slate-500">{{ str_replace('_', ' ', $r->status_kasus) }}</td>
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        @if (auth()->user()->canAnalyze())
                            <a href="{{ route('cybercrime.edit', $r) }}" class="text-brand-600 hover:text-brand-700 text-sm font-medium">Edit</a>
                        @endif
                        @if (auth()->user()->isAdmin())
                            <form method="POST" action="{{ route('cybercrime.destroy', $r) }}" class="inline" onsubmit="return confirm('Hapus laporan {{ $r->nomor_laporan }}?');">
                                @csrf @method('DELETE')
                                <button class="text-danger-600 hover:text-danger-700 text-sm font-medium ml-3">Hapus</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="px-5 py-10 text-center text-sm text-slate-500">Tidak ada data yang cocok dengan filter.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-5 py-3 border-t border-slate-200/60">
        {{ $records->links() }}
    </div>
</div>
@endsection
