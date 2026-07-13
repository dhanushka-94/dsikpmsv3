@extends('layouts.app')

@section('title', 'Gantt — '.$project->name)
@section('page-title', 'Gantt chart')
@section('page-subtitle', $project->name)

@section('actions')
    <a href="{{ route('projects.tasks.board', $project) }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Task board</a>
    <a href="{{ route('projects.show', $project) }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Project</a>
    @if($canManage)
        <a href="{{ route('projects.tasks.create', $project) }}" class="rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-700">Add task</a>
    @endif
@endsection

@section('content')
    <div class="mb-5 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-lg font-extrabold">Timeline</h2>
            <p class="text-sm text-muted">Task schedule across the project duration.</p>
        </div>
        <div class="p-4">
            @if($ganttTasks->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-14 text-center">
                    <p class="font-semibold">No tasks yet</p>
                    <p class="mt-1 text-sm text-muted">Create tasks to see them on the timeline.</p>
                    @if($canManage)
                        <a href="{{ route('projects.tasks.create', $project) }}" class="mt-4 inline-flex rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white">Add task</a>
                    @endif
                </div>
            @else
                <div id="gantt-root" class="overflow-x-auto"></div>
            @endif
        </div>
    </div>

    @if($tasks->isNotEmpty())
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-muted">
                        <tr>
                            <th class="px-4 py-3">Task</th>
                            <th class="px-4 py-3">Priority</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Assignees</th>
                            <th class="px-4 py-3">Start</th>
                            <th class="px-4 py-3">End</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($tasks as $task)
                            <tr>
                                <td class="px-4 py-3 font-semibold">
                                    @if($canManage)
                                        <a href="{{ route('tasks.edit', $task) }}" class="hover:text-brand-700">{{ $task->title }}</a>
                                    @else
                                        {{ $task->title }}
                                    @endif
                                </td>
                                <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $task->priority->badgeClasses() }}">{{ $task->priority->label() }}</span></td>
                                <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $task->status->badgeClasses() }}">{{ $task->status->label() }}</span></td>
                                <td class="px-4 py-3">{{ $task->assigneeNames() ?: '—' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ dsi_datetime($task->starts_at) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ dsi_datetime($task->ends_at) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.css">
<style>
    .gantt .bar-wrapper.priority-low .bar { fill: #94a3b8; }
    .gantt .bar-wrapper.priority-medium .bar { fill: #0ea5e9; }
    .gantt .bar-wrapper.priority-high .bar { fill: #f59e0b; }
    .gantt .bar-wrapper.priority-urgent .bar { fill: #e31c23; }
    .gantt .grid-header,
    .gantt .grid-row { fill: #fff; }
    .gantt-container { border-radius: 1rem; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.getElementById('gantt-root');
        const tasks = @json($ganttTasks);
        if (!root || !tasks.length || typeof Gantt === 'undefined') return;

        new Gantt(root, tasks, {
            view_mode: 'Day',
            bar_height: 28,
            padding: 18,
            custom_popup_html: function (task) {
                return `<div class="p-2 text-sm"><strong>${task.name}</strong><br>${task.start} → ${task.end}</div>`;
            }
        });
    });
</script>
@endpush
