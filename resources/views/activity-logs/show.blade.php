@extends('layouts.app')

@section('title', 'Activity detail')
@section('page-title', 'Activity detail')
@section('page-subtitle', $log->description)

@section('actions')
    <a href="{{ route('activity-logs.index') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Back to logs</a>
@endsection

@section('content')
    <div class="mx-auto grid max-w-4xl gap-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wider text-muted">When</dt>
                    <dd class="mt-1 font-semibold">{{ $log->created_at->format('Y-m-d H:i:s') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wider text-muted">User</dt>
                    <dd class="mt-1 font-semibold">
                        @if($log->user)
                            <a href="{{ route('activity-logs.user', $log->user) }}" class="text-brand-700 hover:underline">{{ $log->user->displayName() }}</a>
                            <span class="block text-sm font-normal text-muted">{{ $log->user->email }}</span>
                        @else
                            System / Guest
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wider text-muted">Module</dt>
                    <dd class="mt-1 font-semibold">{{ $log->moduleLabel() }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wider text-muted">Action</dt>
                    <dd class="mt-1 font-semibold">{{ $log->actionLabel() }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wider text-muted">IP address</dt>
                    <dd class="mt-1 font-semibold">{{ $log->ip_address ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wider text-muted">Method / URL</dt>
                    <dd class="mt-1 break-all text-sm font-semibold">{{ $log->method ?? '—' }} {{ $log->url ?? '' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-bold uppercase tracking-wider text-muted">Description</dt>
                    <dd class="mt-1 font-semibold">{{ $log->description }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-bold uppercase tracking-wider text-muted">User agent</dt>
                    <dd class="mt-1 text-sm text-muted">{{ $log->user_agent ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        @if(!empty($log->properties))
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold">Properties</h2>
                <pre class="mt-4 overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs leading-relaxed text-slate-100">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        @endif
    </div>
@endsection
