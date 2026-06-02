@extends('layouts.guest')
@section('title', 'Daftar')

@section('content')
<div x-data="{ role: '{{ old('role', 'analis') }}', show: false, show2: false }">
    <div class="space-y-1 mb-6">
        <h1 class="font-display text-2xl font-bold text-slate-900">Daftar Akun Baru</h1>
        <p class="text-sm text-slate-500">Buat akun untuk mulai menganalisis data cybercrime.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 px-4 py-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-lg text-sm">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label class="text-sm font-medium text-slate-700">Nama Lengkap</label>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus
                   class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm" />
        </div>

        <div>
            <label class="text-sm font-medium text-slate-700">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm" />
        </div>

        <div>
            <label class="text-sm font-medium text-slate-700">Instansi <span class="text-slate-400">(opsional)</span></label>
            <input type="text" name="instansi" value="{{ old('instansi') }}"
                   class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm"
                   placeholder="Universitas / Lembaga" />
        </div>

        {{-- ============ Pemilih Peran ============ --}}
        <div>
            <label class="text-sm font-medium text-slate-700">Pilih Peran</label>
            <input type="hidden" name="role" :value="role" />
            <div class="mt-2 grid grid-cols-1 gap-2">
                @php
                    $roles = [
                        ['key' => 'admin',  'title' => 'Administrator', 'desc' => 'Akses penuh: kelola data, analisis, & hapus.', 'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
                        ['key' => 'analis', 'title' => 'Analis Data',   'desc' => 'Input data & jalankan analisis clustering.', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                        ['key' => 'viewer', 'title' => 'Pengamat',      'desc' => 'Lihat dashboard & hasil analisis saja.', 'icon' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'],
                    ];
                @endphp
                @foreach ($roles as $r)
                    <button type="button" @click="role = '{{ $r['key'] }}'"
                            :class="role === '{{ $r['key'] }}' ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-500/20' : 'border-slate-200 hover:border-slate-300'"
                            class="flex items-center gap-3 p-3 rounded-xl border text-left transition">
                        <span :class="role === '{{ $r['key'] }}' ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-500'"
                              class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 transition">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $r['icon'] }}"/>
                            </svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-slate-800">{{ $r['title'] }}</span>
                            <span class="block text-xs text-slate-500">{{ $r['desc'] }}</span>
                        </span>
                        <svg x-show="role === '{{ $r['key'] }}'" class="w-5 h-5 text-brand-600 ml-auto shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.5 7.5a1 1 0 01-1.42 0l-3.5-3.5a1 1 0 011.42-1.42l2.79 2.79 6.79-6.79a1 1 0 011.42 0z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ============ Password ============ --}}
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-sm font-medium text-slate-700">Kata Sandi</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" name="password" required
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm pr-9" />
                    <button type="button" @click="show = !show" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 mt-0.5">
                        <svg x-show="!show" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="show" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Konfirmasi</label>
                <input :type="show ? 'text' : 'password'" name="password_confirmation" required
                       class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm" />
            </div>
        </div>
        <p class="text-xs text-slate-400">Minimal 6 karakter.</p>

        <button class="w-full py-2.5 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold shadow-lg shadow-brand-600/20 transition">
            Daftar &amp; Masuk
        </button>
    </form>

    <div class="mt-6 text-center text-sm text-slate-500">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="text-brand-600 hover:text-brand-700 font-medium">Masuk</a>
    </div>
</div>
@endsection
