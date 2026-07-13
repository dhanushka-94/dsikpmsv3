@extends('layouts.app')

@section('title', 'Changelog')
@section('page-title', 'Changelog')
@section('page-subtitle', 'Software version history (Super Admin only)')

@section('content')
    <div class="mb-6 rounded-3xl border border-brand-100 bg-brand-50/70 p-5 shadow-sm">
        <p class="text-[11px] font-bold uppercase tracking-wider text-brand-700/70">Current version</p>
        <p class="mt-1 text-3xl font-extrabold text-brand-700">v{{ $version }}</p>
        @if($releasedAt)
            <p class="mt-1 text-sm font-semibold text-brand-700/80">Released {{ $releasedAt }}</p>
        @endif
    </div>

    <div class="space-y-4">
        @forelse($releases as $release)
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-lg font-extrabold text-ink">v{{ $release['version'] }}</p>
                        <p class="mt-0.5 text-sm font-semibold text-slate-600">{{ $release['title'] ?? 'Release' }}</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $release['date'] ?? '—' }}</span>
                </div>
                <ul class="mt-4 space-y-2">
                    @foreach(($release['changes'] ?? []) as $change)
                        <li class="flex gap-2 text-sm text-slate-700">
                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-600"></span>
                            <span class="break-words [overflow-wrap:anywhere]">{{ $change }}</span>
                        </li>
                    @endforeach
                </ul>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm">
                <p class="font-semibold">No changelog entries yet.</p>
            </div>
        @endforelse
    </div>
@endsection
