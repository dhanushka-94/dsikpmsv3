@extends('layouts.app')

@section('title', 'Activity logs')
@section('page-title', 'Activity logs')
@section('page-subtitle', 'Complete audit trail of user actions')

@section('content')
    <form method="GET" class="mb-5 grid gap-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-6">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search description, action..." class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100 lg:col-span-2">

        <select name="user_id" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm">
            <option value="">All users</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->displayName() }}</option>
            @endforeach
        </select>

        <select name="module" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm">
            <option value="">All modules</option>
            @foreach($modules as $module)
                <option value="{{ $module }}" @selected(request('module') === $module)>{{ str($module)->replace('_', ' ')->title() }}</option>
            @endforeach
        </select>

        <select name="action" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm">
            <option value="">All actions</option>
            @foreach($actions as $action)
                <option value="{{ $action }}" @selected(request('action') === $action)>{{ str($action)->replace('_', ' ')->title() }}</option>
            @endforeach
        </select>

        <div class="flex gap-2 lg:col-span-6">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm">
            <button class="rounded-2xl bg-ink px-4 py-2.5 text-sm font-bold text-white">Filter</button>
            <a href="{{ route('activity-logs.index') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Reset</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-muted">
                    <tr>
                        <th class="px-4 py-3">When</th>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Module</th>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3">IP</th>
                        <th class="px-4 py-3 text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <p class="font-semibold">{{ $log->created_at->format('Y-m-d') }}</p>
                                <p class="text-xs text-muted">{{ $log->created_at->format('H:i:s') }}</p>
                            </td>
                            <td class="px-4 py-3">
                                @if($log->user)
                                    <a href="{{ route('activity-logs.user', $log->user) }}" class="font-semibold hover:text-brand-700">{{ $log->user->displayName() }}</a>
                                    <p class="text-xs text-muted">{{ $log->user->email }}</p>
                                @else
                                    <span class="text-muted">System / Guest</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold">{{ $log->moduleLabel() }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-700">{{ $log->actionLabel() }}</span>
                            </td>
                            <td class="px-4 py-3 max-w-md">{{ $log->description }}</td>
                            <td class="px-4 py-3 text-xs text-muted">{{ $log->ip_address ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('activity-logs.show', $log) }}" class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-muted">No activity logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $logs->links() }}</div>
        @endif
    </div>
@endsection
