@extends('layouts.app')

@section('title', 'User activity')
@section('page-title', 'Activity — '.$user->displayName())
@section('page-subtitle', $user->email)

@section('actions')
    <a href="{{ route('activity-logs.index', ['user_id' => $user->id]) }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">All filters</a>
    <a href="{{ route('users.show', $user) }}" class="rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-700">User profile</a>
@endsection

@section('content')
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-muted">
                    <tr>
                        <th class="px-4 py-3">When</th>
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
                                <p class="text-xs text-muted">{{ dsi_time($log->created_at, true) }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold">{{ $log->moduleLabel() }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-700">{{ $log->actionLabel() }}</span>
                            </td>
                            <td class="px-4 py-3">{{ $log->description }}</td>
                            <td class="px-4 py-3 text-xs text-muted">{{ $log->ip_address ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('activity-logs.show', $log) }}" class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-muted">No activity recorded for this user yet.</td>
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
