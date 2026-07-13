@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Welcome back, '.$user->displayName())

@section('content')
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Role</p>
            <p class="mt-2 text-xl font-extrabold text-brand-600">{{ $user->role->label() }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Department</p>
            <p class="mt-2 text-xl font-extrabold">{{ $user->department?->name ?? '—' }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Designation</p>
            <p class="mt-2 text-xl font-extrabold">{{ $user->designation?->name ?? '—' }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">EPF Number</p>
            <p class="mt-2 text-xl font-extrabold">{{ $user->epf_number ?? '—' }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="text-lg font-bold">Your profile</h2>
            <div class="mt-5 flex flex-col gap-5 sm:flex-row sm:items-center">
                @if($user->profilePictureUrl())
                    <img src="{{ $user->profilePictureUrl() }}" alt="" class="h-24 w-24 rounded-3xl object-cover ring-4 ring-brand-50">
                @else
                    <div class="flex h-24 w-24 items-center justify-center rounded-3xl bg-brand-50 text-3xl font-extrabold text-brand-600">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
                <dl class="grid flex-1 gap-3 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Full name</dt>
                        <dd class="mt-1 font-semibold">{{ $user->displayName() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Email / Username</dt>
                        <dd class="mt-1 font-semibold">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Reports to</dt>
                        <dd class="mt-1 font-semibold">{{ $user->parent?->displayName() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Status</dt>
                        <dd class="mt-1">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="rounded-3xl bg-gradient-to-br from-brand-600 to-brand-800 p-6 text-white shadow-lg shadow-brand-600/20">
            <p class="text-sm font-semibold text-brand-100">DSI KPI Monitoring System</p>
            <h3 class="mt-2 text-2xl font-extrabold leading-tight">Track performance with clarity.</h3>
            <p class="mt-3 text-sm text-brand-100/90">Your personalized KPI workspace starts here. More modules can be added as the system grows.</p>
            @if($user->canManageUsers())
                <a href="{{ route('users.index') }}" class="mt-6 inline-flex rounded-2xl bg-white px-4 py-2.5 text-sm font-bold text-brand-700 hover:bg-brand-50">
                    Manage users
                </a>
            @endif
        </div>
    </div>
@endsection
