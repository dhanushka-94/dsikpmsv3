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
    <form method="GET" class="mb-6 grid gap-3 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-6">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or reference..." class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100 lg:col-span-2">
        <select name="year" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
            <option value="">All years</option>
            @foreach($years as $value => $label)
                <option value="{{ $value }}" @selected((string) request('year') === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
            <option value="">All statuses</option>
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="company_id" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
            <option value="">All companies</option>
            @foreach($companies as $company)
                <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->name }}</option>
            @endforeach
        </select>
        <select name="department_id" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
            <option value="">All departments</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->displayName() }}</option>
            @endforeach
        </select>
        <select name="project_category_id" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
            <option value="">All categories</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((string) request('project_category_id') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <div class="flex gap-2 lg:col-span-6">
            <button class="rounded-2xl bg-ink px-4 py-3 text-sm font-bold text-white">Filter</button>
            <a href="{{ route('projects.index') }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600">Reset</a>
        </div>
    </form>

    <div class="space-y-5">
        @forelse($projects as $project)
            <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-4 sm:px-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $project->status->badgeClasses() }}">{{ $project->status->label() }}</span>
                                <span class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-slate-600 ring-1 ring-slate-200">{{ $project->year }}</span>
                                @if($project->category)
                                    <span class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-slate-600 ring-1 ring-slate-200">{{ $project->category->name }}</span>
                                @endif
                                @if($project->company)
                                    <span class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-slate-600 ring-1 ring-slate-200">{{ $project->company->name }}</span>
                                @endif
                                @if($project->plant)
                                    <span class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-slate-600 ring-1 ring-slate-200">{{ $project->plant->name }}</span>
                                @endif
                            </div>
                            <x-long-text
                                :text="$project->name"
                                :href="route('projects.show', $project)"
                                class="mt-2.5 block text-xl font-extrabold tracking-tight text-ink hover:text-brand-700"
                            />
                            @if($project->reference_number)
                                <p class="mt-1 break-words text-sm text-muted [overflow-wrap:anywhere]">Ref {{ $project->reference_number }}</p>
                            @endif
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            <a href="{{ route('projects.show', $project) }}" class="rounded-2xl bg-ink px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800">View</a>
                            <a href="{{ route('projects.tasks.board', $project) }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Board</a>
                            <a href="{{ route('projects.tasks.gantt', $project) }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Gantt</a>
                            @if($project->canBeEditedBy(auth()->user()))
                                <a href="{{ route('projects.edit', $project) }}" class="rounded-2xl border border-brand-200 bg-brand-50 px-4 py-2.5 text-sm font-bold text-brand-700 hover:bg-brand-100">Edit</a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="px-5 py-4 sm:px-6">
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3.5">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Tasks</p>
                            <p class="mt-1.5 text-2xl font-extrabold text-ink">{{ $project->tasks_count }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3.5">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Start</p>
                            <p class="mt-1.5 text-base font-extrabold text-ink">{{ optional($project->start_date)->format('Y-m-d') ?? '—' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3.5">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-muted">End</p>
                            <p class="mt-1.5 text-base font-extrabold text-ink">{{ optional($project->end_date)->format('Y-m-d') ?? '—' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3.5">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Team</p>
                            <p class="mt-1.5 text-2xl font-extrabold text-ink">{{ $project->users->count() }}</p>
                        </div>
                    </div>

                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <div class="mb-2.5 flex items-center justify-between gap-3">
                            <h3 class="text-sm font-bold">Assigned users</h3>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">{{ $project->users->count() }}</span>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @forelse($project->users as $member)
                                <div class="rounded-2xl border border-slate-200 bg-white px-3.5 py-2.5 shadow-sm">
                                    <p class="truncate text-sm font-bold text-ink">{{ $member->displayName() }}</p>
                                    <p class="mt-0.5 truncate text-xs font-semibold text-muted">{{ $member->designation?->name ?? '—' }}</p>
                                </div>
                            @empty
                                <p class="text-sm text-muted">No users assigned yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm">
                <p class="font-semibold">No projects found.</p>
                <p class="mt-2 text-sm text-muted">Try adjusting filters{{ $canManage ? ', or create a new project.' : '.' }}</p>
            </div>
        @endforelse
    </div>

    @if($projects->hasPages())
        <div class="mt-6">{{ $projects->links() }}</div>
    @endif
@endsection
