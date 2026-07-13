@extends('layouts.app')

@section('title', 'Projects')
@section('page-title', 'Projects')
@section('page-subtitle', $canManage ? 'Manage all projects' : 'Projects assigned to you')

@section('actions')
    @if($canManage)
        <a href="{{ route('project-categories.index') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Categories</a>
        <a href="{{ route('projects.create') }}" class="rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-700">Add project</a>
    @endif
@endsection

@section('content')
    <form method="GET" class="mb-5 grid gap-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-6">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or reference..." class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100 lg:col-span-2">
        <select name="year" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm">
            <option value="">All years</option>
            @foreach($years as $value => $label)
                <option value="{{ $value }}" @selected((string) request('year') === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm">
            <option value="">All statuses</option>
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="project_category_id" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm">
            <option value="">All categories</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((string) request('project_category_id') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <select name="department_id" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm">
            <option value="">All departments</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->displayName() }}</option>
            @endforeach
        </select>
        <div class="flex gap-2 lg:col-span-6">
            <button class="rounded-2xl bg-ink px-4 py-2.5 text-sm font-bold text-white">Filter</button>
            <a href="{{ route('projects.index') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Reset</a>
        </div>
    </form>

    <div class="space-y-4">
        @forelse($projects as $project)
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('projects.show', $project) }}" class="text-lg font-extrabold tracking-tight hover:text-brand-700">{{ $project->name }}</a>
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $project->status->badgeClasses() }}">{{ $project->status->label() }}</span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ $project->year }}</span>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Tasks</p>
                                <p class="font-semibold">{{ $project->tasks_count }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Reference</p>
                                <p class="font-semibold">{{ $project->reference_number ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Start</p>
                                <p class="font-semibold">{{ optional($project->start_date)->format('Y-m-d') ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">End</p>
                                <p class="font-semibold">{{ optional($project->end_date)->format('Y-m-d') ?? '—' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('projects.show', $project) }}" class="rounded-xl bg-ink px-3 py-2 text-xs font-bold text-white">View</a>
                        <a href="{{ route('projects.tasks.board', $project) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold">Board</a>
                        <a href="{{ route('projects.tasks.gantt', $project) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold">Gantt</a>
                        @if($project->canBeEditedBy(auth()->user()))
                            <a href="{{ route('projects.edit', $project) }}" class="rounded-xl border border-brand-200 bg-brand-50 px-3 py-2 text-xs font-semibold text-brand-700">Edit</a>
                        @endif
                    </div>
                </div>

                <div class="mt-5 border-t border-slate-100 pt-4">
                    <h3 class="mb-3 text-sm font-bold">Assigned users</h3>

                    <div class="flex flex-wrap gap-2">
                        @forelse($project->users as $member)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2">
                                <p class="truncate text-xs font-bold text-ink">{{ $member->displayName() }}</p>
                                <p class="truncate text-[10px] font-semibold text-muted">{{ $member->designation?->name ?? '—' }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-muted">No users assigned yet.</p>
                        @endforelse
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm">
                <p class="font-semibold">No projects found.</p>
            </div>
        @endforelse
    </div>

    @if($projects->hasPages())
        <div class="mt-5">{{ $projects->links() }}</div>
    @endif
@endsection
