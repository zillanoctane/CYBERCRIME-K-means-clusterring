@extends('layouts.app')
@section('title', 'Edit Laporan')
@section('page-title', 'Edit Laporan Cybercrime')
@section('page-subtitle', $record->nomor_laporan)

@section('content')
<form method="POST" action="{{ route('cybercrime.update', $record) }}" class="bg-white rounded-2xl shadow-soft border border-slate-200/60">
    @csrf @method('PUT')
    <div class="p-5">
        @include('cybercrime._form', ['record' => $record])
    </div>
    <div class="px-5 py-3 bg-slate-50 border-t border-slate-200/60 rounded-b-2xl flex justify-between">
        <a href="{{ route('cybercrime.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800">Batal</a>
        <button class="px-4 py-2 text-sm rounded-lg bg-brand-600 text-white hover:bg-brand-700">Perbarui</button>
    </div>
</form>
@endsection
