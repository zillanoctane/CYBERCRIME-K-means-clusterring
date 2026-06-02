@extends('layouts.app')
@section('title', 'Import Data')
@section('page-title', 'Import Data Cybercrime')
@section('page-subtitle', 'Unggah berkas CSV / XLSX untuk batch insert')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <form method="POST" action="{{ route('cybercrime.import') }}" enctype="multipart/form-data" class="lg:col-span-2 bg-white rounded-2xl shadow-soft border border-slate-200/60 p-6">
        @csrf

        <div class="text-center py-10 border-2 border-dashed border-slate-200 rounded-xl mb-4 hover:border-brand-400 transition" x-data="{ name:'' }">
            <svg class="w-12 h-12 mx-auto text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.9 5 5 0 019.46-1.88A4.5 4.5 0 0117 16H7zm5-1V8m0 7l-3-3m3 3l3-3"/>
            </svg>
            <p class="mt-3 text-sm text-slate-600">Drag & drop atau klik untuk memilih berkas</p>
            <p class="text-xs text-slate-400">CSV, XLSX · maks. 10 MB</p>
            <input type="file" name="file" accept=".csv,.xlsx,.txt" required class="mt-4 mx-auto block text-sm" @change="name = $event.target.files[0]?.name || ''" />
            <p class="mt-2 text-xs font-mono text-slate-600" x-text="name"></p>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('cybercrime.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800">Batal</a>
            <button class="px-4 py-2 text-sm rounded-lg bg-brand-600 text-white hover:bg-brand-700">Mulai Import</button>
        </div>
    </form>

    <div class="bg-white rounded-2xl shadow-soft border border-slate-200/60 p-6">
        <h3 class="font-display font-semibold text-slate-800 mb-2">Format Berkas</h3>
        <p class="text-xs text-slate-500 mb-3">Header (baris pertama) yang harus ada:</p>
        <div class="text-[11px] text-slate-700 font-mono bg-slate-50 rounded-lg p-3 border border-slate-100 leading-relaxed">
            nomor_laporan, tanggal_kejadian, tanggal_laporan, jenis_kejahatan, sub_jenis, modus_operandi, platform, provinsi, kota_kabupaten, latitude, longitude, usia_korban, jenis_kelamin_korban, pekerjaan_korban, pendidikan_korban, estimasi_kerugian, jumlah_korban, tingkat_keparahan, status_kasus, tersangka_teridentifikasi, sumber_data, keterangan
        </div>
        <p class="text-xs text-slate-500 mt-3">Contoh berkas: <code class="font-mono text-brand-700">data/sample-cybercrime.csv</code></p>

        <hr class="my-4 border-slate-100" />
        <h4 class="text-sm font-semibold text-slate-800">Aturan</h4>
        <ul class="mt-2 text-xs text-slate-600 space-y-1 list-disc list-inside">
            <li>Baris dengan <em>nomor_laporan</em> sama akan ditimpa (upsert).</li>
            <li>Tanggal: format ISO (YYYY-MM-DD) atau dikenali oleh Excel.</li>
            <li>Kolom enum (keparahan, status_kasus) tidak case-sensitive.</li>
            <li>Baris yang gagal divalidasi akan dilewati dan dilaporkan.</li>
        </ul>
    </div>
</div>
@endsection
