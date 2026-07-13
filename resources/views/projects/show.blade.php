@extends('layouts.app')

@section('title', $project->name)
@section('page-title', $project->name)
@section('page-subtitle', $project->reference_number ?? 'Project details')

@section('actions')
    @if($canEdit)
        <a href="{{ route('projects.edit', $project) }}" class="rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-700">Edit</a>
    @endif
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $project->status->badgeClasses() }}">{{ $project->status->label() }}</span>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ $project->year }}</span>
                </div>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Category</dt>
                        <dd class="mt-1 font-semibold">{{ $project->category?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Department</dt>
                        <dd class="mt-1 font-semibold">{{ $project->department?->displayName() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Reference number</dt>
                        <dd class="mt-1 font-semibold">{{ $project->reference_number ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Created by</dt>
                        <dd class="mt-1 font-semibold">{{ $project->creator?->displayName() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Start date</dt>
                        <dd class="mt-1 font-semibold">{{ optional($project->start_date)->format('Y-m-d') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">End date</dt>
                        <dd class="mt-1 font-semibold">{{ optional($project->end_date)->format('Y-m-d') ?? '—' }}</dd>
                    </div>
                </dl>
                @if($project->description)
                    <div class="mt-5 border-t border-slate-100 pt-5">
                        <h3 class="text-sm font-bold">Description</h3>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $project->description }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-bold">Assigned team</h3>
                <div class="mt-4 space-y-3">
                    @forelse($project->users as $member)
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-slate-50/80 px-3 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">{{ $member->displayName() }}</p>
                                <p class="truncate text-xs text-muted">{{ $member->designation?->name ?? 'No designation' }}</p>
                            </div>
                            <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-slate-600 ring-1 ring-slate-200">
                                {{ \App\Enums\ProjectPermission::from($member->pivot->permission)->label() }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-muted">No users assigned.</p>
                    @endforelse
                </div>
            </div>

            @if($canManage)
                <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Delete this project permanently?')">
                    @csrf
                    @method('DELETE')
                    <button class="w-full rounded-2xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-bold text-red-700 hover:bg-red-100">
                        Delete project
                    </button>
                </form>
            @endif
        </div>
    </div>
@endsection
