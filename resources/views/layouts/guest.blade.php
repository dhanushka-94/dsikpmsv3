<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') — {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/dsi-logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface font-sans text-ink antialiased">
    <div class="relative min-h-screen overflow-hidden">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(227,28,35,0.12),_transparent_40%),radial-gradient(circle_at_bottom_left,_rgba(227,28,35,0.08),_transparent_35%)]"></div>
        <div class="relative mx-auto flex min-h-screen max-w-md flex-col justify-center px-4 py-10">
            <div class="mb-8 text-center">
                <img src="{{ asset('images/dsi-logo.png') }}" alt="DSI Footwear" class="mx-auto h-16 w-auto object-contain">
                <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-ink">DSI KPI Monitoring System</h1>
                <p class="mt-1 text-sm text-muted">@yield('subtitle', 'Sign in with your email to continue')</p>
            </div>

            <div class="rounded-3xl border border-white/70 bg-white p-6 shadow-[0_20px_60px_-30px_rgba(28,36,52,0.35)] sm:p-8">
                @yield('content')
            </div>

            <p class="mt-8 text-center text-xs text-slate-400">
                Developed by <span class="font-semibold text-slate-500">olexto Digital Solutions</span>
            </p>
        </div>
    </div>
</body>
</html>
