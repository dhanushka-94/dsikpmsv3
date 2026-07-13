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

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-muted">
                    <tr>
                        <th class="px-4 py-3">Project</th>
                        <th class="px-4 py-3">Year</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Department</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Team</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($projects as $project)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3">
                                <a href="{{ route('projects.show', $project) }}" class="font-semibold hover:text-brand-700">{{ $project->name }}</a>
                                <p class="text-xs text-muted">{{ $project->reference_number ?? 'No reference' }}</p>
                            </td>
                            <td class="px-4 py-3 font-semibold">{{ $project->year }}</td>
                            <td class="px-4 py-3">{{ $project->category?->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $project->department?->displayName() ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $project->status->badgeClasses() }}">
                                    {{ $project->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $project->users_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('projects.show', $project) }}" class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold">View</a>
                                    @if($project->canBeEditedBy(auth()->user()))
                                        <a href="{{ route('projects.edit', $project) }}" class="rounded-xl border border-brand-200 bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700">Edit</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-muted">No projects found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($projects->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $projects->links() }}</div>
        @endif
    </div>
@endsection
