<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Masuk') | {{ config('app.name', 'SIANCEK') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800&family=plus-jakarta-sans:600,700,800" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
      tailwind.config = { theme: { extend: { fontFamily: { sans:['Inter','ui-sans-serif'], display:['Plus Jakarta Sans','Inter']}, colors:{ brand:{50:'#eef4ff',500:'#3a5cff',600:'#2540ed',700:'#1f33d3',900:'#202c83',950:'#161c4d'}}}}};
    </script>
    <style>
      body { background: radial-gradient(at 25% 25%, #1f2da9 0, #161c4d 60%); min-height:100vh; }
      .bg-grid { background-image: linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px); background-size: 32px 32px; }
    </style>
</head>
<body class="font-sans text-slate-800 antialiased">
<div class="min-h-screen bg-grid flex">
    {{-- Left panel: branding --}}
    <div class="hidden lg:flex w-1/2 flex-col justify-between text-white p-12">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-white/15 backdrop-blur flex items-center justify-center font-display font-bold text-xl">S</div>
            <div>
                <div class="font-display font-bold text-xl">SIANCEK</div>
                <div class="text-xs uppercase tracking-widest text-white/70">Cybercrime Analytics</div>
            </div>
        </div>

        <div class="space-y-6">
            <span class="inline-block px-3 py-1 text-xs uppercase tracking-wider bg-white/10 rounded-full border border-white/20">Penelitian Akademik</span>
            <h2 class="font-display text-4xl xl:text-5xl font-bold leading-tight">
                Pemetaan karakteristik &amp; tren <span class="text-cyan-300">kejahatan siber</span> berbasis K-Means.
            </h2>
            <p class="text-white/70 max-w-md leading-relaxed">
                Sistem informasi berbasis web yang mengelompokkan laporan tindak pidana siber menggunakan <em>unsupervised learning</em>—mendukung pengambilan keputusan berbasis data.
            </p>
            <ul class="space-y-2 text-sm text-white/80">
                @foreach (['Elbow + Silhouette + Davies-Bouldin', 'Visualisasi cluster PCA 2D', 'Ekspor laporan PDF & Excel', 'CRISP-DM workflow'] as $f)
                    <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-cyan-300"></span>{{ $f }}</li>
                @endforeach
            </ul>
        </div>

        <div class="text-xs text-white/50">&copy; {{ date('Y') }} SIANCEK · Teknik Informatika</div>
    </div>

    {{-- Right panel: form --}}
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 lg:p-12">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-8">
            @yield('content')
        </div>
    </div>
</div>
</body>
</html>
