<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/dsi-logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('styles')
</head>
<body class="min-h-screen bg-surface font-sans text-ink antialiased">
    <div class="min-h-screen lg:flex" x-data="{ sidebarOpen: false }">
        @include('partials.confirm-delete-modal')
        {{-- Mobile overlay --}}
        <div
            x-show="sidebarOpen"
            x-transition.opacity
            class="fixed inset-0 z-40 bg-ink/40 lg:hidden"
            @click="sidebarOpen = false"
        ></div>

        {{-- Sidebar --}}
        <aside
            class="fixed inset-y-0 left-0 z-50 flex h-screen w-72 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-300 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:self-start"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        >
            <div class="shrink-0 flex items-center gap-3 border-b border-slate-100 px-5 py-5">
                <img src="{{ asset('images/dsi-logo.png') }}" alt="DSI Footwear" class="h-12 w-auto object-contain">
                <div class="min-w-0">
                    <p class="truncate text-sm font-extrabold tracking-tight text-brand-600">DSI KPI</p>
                    <p class="truncate text-xs text-muted">Monitoring System</p>
                </div>
            </div>

            <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <p class="px-3 pb-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">Main</p>

                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('dashboard') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-ink' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l9-9 9 9M5 10v10h14V10"/></svg>
                    Dashboard
                </a>

                <a href="{{ route('quick-access') }}"
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('quick-access') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-ink' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Quick access
                </a>

                <a href="{{ route('projects.index') }}"
                   class="flex items-center gap-3 rounded-xl border px-3 py-2.5 text-sm font-extrabold transition {{ request()->routeIs('projects.*') && ! request()->routeIs('project-categories.*') ? 'border-brand-200 bg-brand-600 text-white shadow-sm' : 'border-brand-100 bg-brand-50 text-brand-700 hover:border-brand-200 hover:bg-brand-100' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h18M3 12h18M3 17h18"/></svg>
                    Projects
                </a>

                <a href="{{ route('tasks.index') }}"
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('tasks.index') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-ink' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5h12M9 12h12M9 19h12M4 5h.01M4 12h.01M4 19h.01"/></svg>
                    Task list
                </a>

                <a href="{{ route('tasks.board') }}"
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('tasks.board') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-ink' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                    Task board
                </a>

                <a href="{{ route('kpis.index') }}"
                   class="flex items-center gap-3 rounded-xl border px-3 py-2.5 text-sm font-extrabold transition {{ request()->routeIs('kpis.*') && ! request()->routeIs('kpi-categories.*') ? 'border-brand-200 bg-brand-600 text-white shadow-sm' : 'border-brand-100 bg-brand-50 text-brand-700 hover:border-brand-200 hover:bg-brand-100' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                    KPIs
                </a>

                <p class="px-3 pb-1 pt-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Account</p>

                <a href="{{ route('profile.edit') }}"
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('profile.*') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-ink' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    My profile
                </a>

                @if(auth()->user()->canManageUsers())
                    <p class="px-3 pb-1 pt-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Administration</p>

                    <p class="px-3 pb-1 pt-2 text-[10px] font-bold uppercase tracking-wider text-slate-300">People</p>

                    <a href="{{ route('users.index') }}"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('users.index', 'users.create', 'users.show', 'users.edit') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-ink' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm12 10v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                        Users
                    </a>

                    <a href="{{ route('users.tree') }}"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('users.tree*') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-ink' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 7h6M9 12h6m-7 5h8M5 4h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5a1 1 0 011-1z"/></svg>
                        Users tree
                    </a>

                    <a href="{{ route('departments.index') }}"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('departments.*') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-ink' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                        Departments
                    </a>

                    <a href="{{ route('designations.index') }}"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('designations.*') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-ink' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-7 8h8a2 2 0 002-2V7.5L14.5 4H8a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Designations
                    </a>

                    <p class="px-3 pb-1 pt-3 text-[10px] font-bold uppercase tracking-wider text-slate-300">Organization</p>

                    <a href="{{ route('companies.index') }}"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('companies.*') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-ink' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21h18M9 8h6m-9 4h12M7 21V10a2 2 0 012-2h6a2 2 0 012 2v11"/></svg>
                        Companies
                    </a>

                    <a href="{{ route('plants.index') }}"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('plants.*') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-ink' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 21h16M6 21V8l6-4 6 4v13M10 12h4"/></svg>
                        Plants
                    </a>

                    <p class="px-3 pb-1 pt-3 text-[10px] font-bold uppercase tracking-wider text-slate-300">Setup</p>

                    <a href="{{ route('kpi-categories.index') }}"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('kpi-categories.*') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-ink' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h10M7 12h10M7 17h6"/></svg>
                        KPI categories
                    </a>

                    <a href="{{ route('project-categories.index') }}"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('project-categories.*') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-ink' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h10M7 12h10M7 17h6"/></svg>
                        Project categories
                    </a>

                    <p class="px-3 pb-1 pt-3 text-[10px] font-bold uppercase tracking-wider text-slate-300">System</p>

                    <a href="{{ route('activity-logs.index') }}"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('activity-logs.*') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-ink' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Activity logs
                    </a>

                    @if(auth()->user()->isSuperAdmin())
                        <a href="{{ route('changelog.index') }}"
                           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('changelog.*') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-ink' }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-7 8h8a2 2 0 002-2V7.5L14.5 4H8a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Changelog
                        </a>
                    @endif
                @endif
            </nav>

            <div class="mt-auto shrink-0 border-t border-slate-100 p-4">
                <div class="mb-3 flex items-center gap-3">
                    <a href="{{ route('profile.edit') }}" class="flex min-w-0 flex-1 items-center gap-3 rounded-xl p-1 transition hover:bg-slate-50">
                        @if(auth()->user()->profilePictureUrl())
                            <img src="{{ auth()->user()->profilePictureUrl() }}" alt="" class="h-10 w-10 rounded-full object-cover ring-2 ring-brand-100">
                        @else
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">{{ auth()->user()->displayName() }}</p>
                            <p class="truncate text-xs text-muted">{{ auth()->user()->role->label() }}</p>
                        </div>
                    </a>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700">
                        Sign out
                    </button>
                </form>
                <div class="mt-3 text-center">
                    @if(auth()->user()->isSuperAdmin())
                        <a href="{{ route('changelog.index') }}" class="text-[10px] font-bold text-slate-500 hover:text-brand-700">v{{ app_version() }} · Changelog</a>
                    @else
                        <p class="text-[10px] font-bold text-slate-400">v{{ app_version() }}</p>
                    @endif
                    <p class="mt-1 text-[10px] text-slate-400">Developed by olexto Digital Solutions</p>
                </div>
            </div>
        </aside>

        {{-- Main --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-30 flex items-center justify-between gap-3 border-b border-slate-200 bg-white/90 px-4 py-3 backdrop-blur lg:px-8">
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <button type="button" class="shrink-0 rounded-xl border border-slate-200 p-2 text-slate-600 lg:hidden" @click="sidebarOpen = true">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="min-w-0">
                        <h1 class="break-words text-lg font-bold tracking-tight [overflow-wrap:anywhere] lg:text-xl">@yield('page-title', 'Dashboard')</h1>
                        @hasSection('page-subtitle')
                            <p class="break-words text-sm text-muted [overflow-wrap:anywhere]">@yield('page-subtitle')</p>
                        @endif
                    </div>
                </div>
                <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                    @yield('actions')
                </div>
            </header>

            <main class="flex-1 px-4 py-6 lg:px-8">
                @include('partials.flash')
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
