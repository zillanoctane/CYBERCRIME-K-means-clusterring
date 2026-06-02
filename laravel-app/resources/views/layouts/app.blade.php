<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Dashboard') | {{ config('app.name', 'SIANCEK') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800&family=plus-jakarta-sans:600,700,800" rel="stylesheet" />

    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            fontFamily: {
              sans: ['Inter', 'ui-sans-serif', 'system-ui'],
              display: ['Plus Jakarta Sans', 'Inter', 'ui-sans-serif'],
            },
            colors: {
              brand: {
                50:  '#eef4ff',
                100: '#dbe6ff',
                200: '#bdd2ff',
                300: '#90b1ff',
                400: '#5e83ff',
                500: '#3a5cff',
                600: '#2540ed',
                700: '#1f33d3',
                800: '#1f2da9',
                900: '#202c83',
                950: '#161c4d',
              },
              accent: { 500: '#06b6d4', 600: '#0891b2' },
              danger: { 500: '#ef4444', 600: '#dc2626' },
              warn:   { 500: '#f59e0b', 600: '#d97706' },
              ok:     { 500: '#10b981', 600: '#059669' },
            },
            backgroundImage: {
              'siancek-gradient': 'linear-gradient(135deg, #1F2DA9 0%, #2540ED 40%, #06b6d4 100%)',
              'mesh': 'radial-gradient(at 20% 20%, rgba(58,92,255,0.15) 0px, transparent 40%), radial-gradient(at 80% 0%, rgba(6,182,212,0.15) 0px, transparent 50%), radial-gradient(at 0% 80%, rgba(31,45,169,0.12) 0px, transparent 50%)',
            },
            boxShadow: {
              soft: '0 4px 24px -8px rgba(15,23,42,.08), 0 2px 6px -2px rgba(15,23,42,.06)',
              card: '0 1px 3px rgba(0,0,0,.05), 0 1px 2px rgba(0,0,0,.03)',
            },
          },
        },
      };
    </script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>

    <style>
      [x-cloak] { display: none !important; }
      .glass { backdrop-filter: blur(12px); background: rgba(255,255,255,.7); border: 1px solid rgba(255,255,255,.4); }
      .scrollbar-thin::-webkit-scrollbar { height: 6px; width: 6px; }
      .scrollbar-thin::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
      .metric-card { transition: transform .15s ease, box-shadow .15s ease; }
      .metric-card:hover { transform: translateY(-2px); box-shadow: 0 10px 24px -10px rgba(31,45,169,.25); }
    </style>

    @stack('head')
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-800 h-full">

<div class="min-h-screen flex bg-mesh">
    {{-- ============== SIDEBAR ============== --}}
    <aside x-data="{ open: window.innerWidth > 1024 }"
           :class="open ? 'w-64' : 'w-20'"
           class="hidden lg:flex flex-col bg-brand-950 text-white transition-all duration-200 shadow-xl">
        <div class="px-5 h-16 flex items-center gap-3 border-b border-white/10">
            <div class="w-9 h-9 rounded-lg bg-siancek-gradient flex items-center justify-center font-display font-bold text-white shadow-lg">S</div>
            <div x-show="open" x-transition class="leading-tight">
                <div class="font-display font-bold text-base">SIANCEK</div>
                <div class="text-[10px] uppercase tracking-wider text-brand-200">Cybercrime Analytics</div>
            </div>
            <button @click="open = !open" class="ml-auto text-white/60 hover:text-white" aria-label="Toggle sidebar">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
        <nav class="flex-1 p-3 space-y-1 text-sm">
            @php
                $nav = [
                    ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7m-9 2v8a2 2 0 002 2h2a2 2 0 002-2v-4a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 002 2h2a2 2 0 002-2v-8'],
                    ['route' => 'cybercrime.index', 'label' => 'Data Cybercrime', 'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z'],
                    ['route' => 'analysis.index', 'label' => 'Analisis Cluster', 'icon' => 'M9 19V5l-7 7 7 7zm6-14v14l7-7-7-7z'],
                ];
            @endphp
            @foreach ($nav as $item)
                @php $active = request()->routeIs($item['route']) || request()->routeIs(str_replace('.index','.*', $item['route'])); @endphp
                <a href="{{ route($item['route']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ $active ? 'bg-white/15 text-white' : 'text-brand-100 hover:bg-white/10 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                    </svg>
                    <span x-show="open" x-transition>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
        <div class="p-4 border-t border-white/10 text-[11px] text-brand-200" x-show="open" x-transition>
            <div class="font-medium text-white">SIANCEK v1.0</div>
            <div>K-Means Clustering · CRISP-DM</div>
        </div>
    </aside>

    {{-- ============== MAIN ============== --}}
    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur border-b border-slate-200/70 flex items-center justify-between px-5 sticky top-0 z-30">
            <div>
                <h1 class="font-display font-semibold text-lg text-slate-800">@yield('page-title', 'Dashboard')</h1>
                <p class="text-xs text-slate-500">@yield('page-subtitle', 'Sistem Analisis Klasterisasi Cybercrime')</p>
            </div>
            <div class="flex items-center gap-3">
                @auth
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 transition">
                            <div class="w-7 h-7 rounded-full bg-siancek-gradient flex items-center justify-center text-white text-xs font-bold">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div class="hidden md:block text-left">
                                <div class="text-sm font-medium text-slate-800">{{ auth()->user()->name }}</div>
                                <div class="text-[11px] text-slate-500">{{ auth()->user()->roleLabel() }}</div>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition x-cloak class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl border border-slate-200 py-1.5 text-sm">
                            <div class="px-3 py-2 border-b border-slate-100">
                                <div class="text-xs text-slate-500">Masuk sebagai</div>
                                <div class="font-medium text-slate-800 truncate">{{ auth()->user()->email }}</div>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">@csrf
                                <button class="w-full text-left px-3 py-2 hover:bg-slate-50 text-danger-600">Keluar</button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </header>

        {{-- Flash messages --}}
        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" class="mx-5 mt-4 bg-ok-500/10 border border-ok-500/30 text-ok-600 rounded-lg px-4 py-3 text-sm flex items-center justify-between">
                <span>{{ session('status') }}</span>
                <button @click="show = false" class="text-ok-600/70 hover:text-ok-600">&times;</button>
            </div>
        @endif
        @if ($errors->any())
            <div class="mx-5 mt-4 bg-danger-500/10 border border-danger-500/30 text-danger-600 rounded-lg px-4 py-3 text-sm">
                <div class="font-medium mb-1">Validasi gagal</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="flex-1 p-5">
            @yield('content')
        </div>

        <footer class="px-5 py-4 text-xs text-slate-500 border-t border-slate-200 bg-white/60 backdrop-blur">
            &copy; {{ date('Y') }} SIANCEK · Sistem Analisis Klasterisasi Cybercrime · Penelitian Teknik Informatika
        </footer>
    </main>
</div>

@stack('scripts')
</body>
</html>
