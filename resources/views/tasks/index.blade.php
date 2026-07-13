@extends('layouts.app')

@section('title', 'Task list')
@section('page-title', 'Task list')
@section('page-subtitle', 'All tasks you can access')

@section('actions')
    <a href="{{ route('tasks.board') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Task board</a>
    @if($canCreate)
        <a href="{{ route('tasks.create') }}" class="rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-700">Add task</a>
    @endif
@endsection

@section('content')
    <form method="GET" class="mb-6 grid gap-3 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-6">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search tasks..." class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100 lg:col-span-2">
        <select name="project_id" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
            <option value="">All projects</option>
            @foreach($projects as $project)
                <option value="{{ $project->id }}" @selected((string) request('project_id') === (string) $project->id)>{{ $project->name }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
            <option value="">All statuses</option>
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="priority" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
            <option value="">All priorities</option>
            @foreach($priorities as $value => $label)
                <option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button class="rounded-2xl bg-ink px-4 py-3 text-sm font-bold text-white">Filter</button>
            <a href="{{ route('tasks.index') }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600">Reset</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-muted">
                    <tr>
                        <th class="px-4 py-3">Task</th>
                        <th class="px-4 py-3">Project</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Priority</th>
                        <th class="px-4 py-3">Assignees</th>
                        <th class="px-4 py-3">Dates</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tasks as $task)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3">
                                <p class="font-bold text-ink">{{ $task->title }}</p>
                                @if($task->description)
                                    <p class="mt-0.5 line-clamp-1 text-xs text-muted">{{ $task->description }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($task->project)
                                    <a href="{{ route('projects.show', $task->project) }}" class="font-semibold text-brand-700 hover:underline">{{ $task->project->name }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $task->status->badgeClasses() }}">{{ $task->status->label() }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $task->priority->badgeClasses() }}">{{ $task->priority->label() }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-xs font-semibold text-slate-600">{{ $task->assigneeNames() ?: '—' }}</p>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs font-semibold text-muted">
                                {{ dsi_datetime_short($task->starts_at) }} → {{ dsi_datetime_short($task->ends_at) }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @if($task->project)
                                    <a href="{{ route('projects.tasks.board', $task->project) }}" class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold">Board</a>
                                @endif
                                @if($task->project?->canBeEditedBy(auth()->user()))
                                    <a href="{{ route('tasks.edit', $task) }}" class="rounded-xl border border-brand-200 bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700">Edit</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-14 text-center text-muted">No tasks found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($tasks->hasPages())
        <div class="mt-6">{{ $tasks->links() }}</div>
    @endif
@endsection
