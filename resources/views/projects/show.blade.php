@extends('layouts.app')

@section('title', $project->name)
@section('page-title', $project->name)
@section('page-subtitle', 'Project view')

@section('actions')
    <a href="{{ route('projects.index') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Back to projects</a>
    <a href="{{ route('projects.tasks.board', $project) }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Task board</a>
    <a href="{{ route('projects.tasks.gantt', $project) }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Gantt</a>
    @if($canEdit)
        <a href="{{ route('projects.tasks.create', $project) }}" class="rounded-2xl border border-brand-200 bg-brand-50 px-4 py-2.5 text-sm font-bold text-brand-700">Add task</a>
        <a href="{{ route('projects.edit', $project) }}" class="rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-700">Edit</a>
    @endif
@endsection

@section('content')
    <div class="mb-5 flex flex-wrap items-center gap-2">
        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $project->status->badgeClasses() }}">{{ $project->status->label() }}</span>
        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ $project->year }}</span>
        <span class="text-sm text-muted">{{ $project->tasks->count() }} {{ $project->tasks->count() === 1 ? 'task' : 'tasks' }} · {{ $project->users->count() }} assigned</span>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold">Project details</h2>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Category</dt>
                        <dd class="mt-1 font-semibold">{{ $project->category?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Company</dt>
                        <dd class="mt-1 font-semibold">{{ $project->company?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Plant</dt>
                        <dd class="mt-1 font-semibold">{{ $project->plant?->name ?? '—' }}</dd>
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

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-base font-bold">Tasks</h2>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('projects.tasks.board', $project) }}" class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold">Open board</a>
                        @if($canEdit)
                            <a href="{{ route('projects.tasks.create', $project) }}" class="rounded-xl bg-brand-600 px-3 py-1.5 text-xs font-bold text-white">Add task</a>
                        @endif
                    </div>
                </div>

                <div class="mt-4 divide-y divide-slate-100">
                    @forelse($project->tasks as $task)
                        <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold">{{ $task->title }}</p>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $task->status->badgeClasses() }}">{{ $task->status->label() }}</span>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $task->priority->badgeClasses() }}">{{ $task->priority->label() }}</span>
                                </div>
                                <p class="mt-1 text-xs text-muted">
                                    {{ $task->starts_at->format('Y-m-d H:i') }} → {{ $task->ends_at->format('Y-m-d H:i') }}
                                </p>
                                @if($task->assignees->isNotEmpty())
                                    <p class="mt-2 text-xs text-slate-600">
                                        {{ $task->assignees->map(fn ($u) => $u->displayName().($u->designation ? ' ('.$u->designation->name.')' : ''))->join(', ') }}
                                    </p>
                                @endif
                            </div>
                            @if($canEdit)
                                <a href="{{ route('tasks.edit', $task) }}" class="shrink-0 rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold">Edit</a>
                            @endif
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-muted">No tasks on this project yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-bold">Assigned users</h3>
                <div class="mt-4 space-y-3">
                    @forelse($project->users as $member)
                        @php $enabled = (bool) $member->pivot->is_enabled; @endphp
                        <div class="flex items-center justify-between gap-3 rounded-2xl border px-3 py-3 {{ $enabled ? 'border-slate-100 bg-slate-50/80' : 'border-slate-200 bg-slate-100 opacity-70' }}">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold {{ $enabled ? '' : 'text-slate-500 line-through' }}">{{ $member->displayName() }}</p>
                                <p class="truncate text-xs text-muted">{{ $member->designation?->name ?? 'No designation' }}</p>
                                <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wide text-muted">
                                    {{ \App\Enums\ProjectPermission::from($member->pivot->permission)->label() }}
                                    · {{ $enabled ? 'Enabled' : 'Disabled' }}
                                </p>
                            </div>
                            @if($canEdit)
                                <form method="POST" action="{{ route('projects.users.toggle', [$project, $member]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="rounded-lg px-2 py-1 text-[10px] font-bold {{ $enabled ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                                        {{ $enabled ? 'Disable' : 'Enable' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-muted">No users assigned.</p>
                    @endforelse
                </div>
            </div>

            @if($canManage)
                <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return requestDeleteConfirm(event, { title: 'Delete this project?', message: 'This will permanently delete the project and all of its tasks. Solve the math check to continue.', requireMath: true })">
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
