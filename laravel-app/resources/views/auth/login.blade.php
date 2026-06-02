@extends('layouts.guest')
@section('title', 'Masuk')

@section('content')
<div class="space-y-1 mb-6">
    <div class="lg:hidden flex items-center gap-2 mb-4">
        <div class="w-9 h-9 rounded-lg bg-brand-600 flex items-center justify-center text-white font-display font-bold">S</div>
        <div class="font-display font-bold text-brand-900">SIANCEK</div>
    </div>
    <h1 class="font-display text-2xl font-bold text-slate-900">Selamat Datang Kembali</h1>
    <p class="text-sm text-slate-500">Masuk untuk mengakses dashboard analisis.</p>
</div>

@if ($errors->any())
    <div class="mb-4 px-4 py-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-lg text-sm">
        {{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf
    <div>
        <label class="text-sm font-medium text-slate-700">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus
               class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm"
               placeholder="anda@instansi.go.id" />
    </div>
    <div>
        <label class="text-sm font-medium text-slate-700">Kata Sandi</label>
        <input type="password" name="password" required
               class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm"
               placeholder="••••••••" />
    </div>
    <div class="flex items-center justify-between">
        <label class="inline-flex items-center text-sm text-slate-600">
            <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
            <span class="ml-2">Ingat saya</span>
        </label>
    </div>
    <button type="submit" class="w-full py-2.5 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold shadow-lg shadow-brand-600/20 transition">
        Masuk
    </button>
</form>

<div class="mt-6 text-center text-sm text-slate-500">
    Belum punya akun?
    <a href="{{ route('register') }}" class="text-brand-600 hover:text-brand-700 font-medium">Daftar di sini</a>
</div>

<div class="mt-6 pt-4 border-t border-slate-100 text-[11px] text-slate-400 space-y-1">
    <div class="font-medium text-slate-500"></div>
    <div class="flex justify-between"><span></span><span class="font-mono"></span></div>
    <div class="flex justify-between"><span></span><span class="font-mono"></span></div>
    <div class="flex justify-between"><span></span><span class="font-mono"></span></div>
</div>
@endsection
