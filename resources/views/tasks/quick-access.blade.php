@extends('layouts.app')

@section('title', 'Quick access')
@section('page-title', 'Quick access')
@section('page-subtitle', 'Shortcuts to common work')

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('tasks.index') }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
            <p class="text-sm font-extrabold">Task list</p>
            <p class="mt-1 text-xs text-muted">Browse and filter all your tasks</p>
        </a>
        <a href="{{ route('tasks.board') }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
            <p class="text-sm font-extrabold">Task board</p>
            <p class="mt-1 text-xs text-muted">Move work across status columns</p>
        </a>
        <a href="{{ route('projects.index') }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
            <p class="text-sm font-extrabold">Projects</p>
            <p class="mt-1 text-xs text-muted">Open project details and teams</p>
        </a>
        <a href="{{ route('kpis.index') }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
            <p class="text-sm font-extrabold">KPIs</p>
            <p class="mt-1 text-xs text-muted">Feed values and track results</p>
        </a>
        @if($canCreateTask)
            <a href="{{ route('tasks.create') }}" class="rounded-3xl border border-brand-200 bg-brand-50/70 p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
                <p class="text-sm font-extrabold text-brand-700">Add task</p>
                <p class="mt-1 text-xs text-brand-700/80">Create a new task quickly</p>
            </a>
        @endif
        @if($canManage)
            <a href="{{ route('projects.create') }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
                <p class="text-sm font-extrabold">Add project</p>
                <p class="mt-1 text-xs text-muted">Start a new project record</p>
            </a>
            <a href="{{ route('kpis.create') }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
                <p class="text-sm font-extrabold">Add KPI</p>
                <p class="mt-1 text-xs text-muted">Define a new KPI preset</p>
            </a>
            <a href="{{ route('users.tree') }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
                <p class="text-sm font-extrabold">Users tree</p>
                <p class="mt-1 text-xs text-muted">Browse people by structure</p>
            </a>
        @endif
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-base font-bold">Open tasks</h2>
                <a href="{{ route('tasks.index', ['status' => 'in_progress']) }}" class="text-xs font-bold text-brand-700 hover:underline">View all</a>
            </div>
            <div class="space-y-3">
                @forelse($openTasks as $task)
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate font-bold text-ink">{{ $task->title }}</p>
                                <p class="mt-0.5 text-xs text-muted">{{ $task->project?->name ?? '—' }}</p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $task->status->badgeClasses() }}">{{ $task->status->label() }}</span>
                        </div>
                        <p class="mt-2 text-[11px] font-semibold text-slate-500">Due {{ dsi_datetime_short($task->ends_at) }}</p>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-muted">No open tasks right now.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-base font-bold">Your projects</h2>
                <a href="{{ route('projects.index') }}" class="text-xs font-bold text-brand-700 hover:underline">View all</a>
            </div>
            <div class="space-y-3">
                @forelse($projects as $project)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3">
                        <div class="min-w-0">
                            <div>
                                <x-long-text :text="$project->name" :href="route('projects.show', $project)" :lines="2" class="font-bold text-ink hover:text-brand-700" />
                            </div>
                            <p class="mt-0.5 text-xs text-muted">{{ $project->year }} · {{ $project->status->label() }}</p>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('projects.tasks.board', $project) }}" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold">Board</a>
                            <a href="{{ route('projects.show', $project) }}" class="rounded-xl bg-ink px-3 py-1.5 text-xs font-bold text-white">Open</a>
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-muted">No projects assigned yet.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
