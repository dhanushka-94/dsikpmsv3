@extends('layouts.app')

@section('title', 'Edit task')
@section('page-title', 'Edit task')
@section('page-subtitle', $task->title)

@section('content')
    <form method="POST" action="{{ route('tasks.update', $task) }}" class="mx-auto max-w-4xl space-y-6">
        @csrf
        @method('PUT')
        @include('tasks._form', ['task' => $task, 'selectedProject' => $task->project])
        <div class="flex flex-wrap gap-3">
            <button class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white hover:bg-brand-700">Save changes</button>
            <a href="{{ route('projects.tasks.board', $task->project) }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600">Cancel</a>
            <button form="delete-task" class="ml-auto rounded-2xl border border-red-200 bg-red-50 px-5 py-3 text-sm font-bold text-red-700">Delete</button>
        </div>
    </form>

    @if($task->assignees->isNotEmpty())
        <div class="mx-auto mt-6 max-w-4xl rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-bold">Assignee access</h2>
            <p class="mt-1 text-sm text-muted">Disable an assignee to pause their assignment without removing them from the task.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($task->assignees as $assignee)
                    @php $enabled = (bool) $assignee->pivot->is_enabled; @endphp
                    <div class="flex items-center gap-2 rounded-2xl border px-3 py-2 {{ $enabled ? 'border-slate-200 bg-slate-50' : 'border-slate-200 bg-slate-100 opacity-70' }}">
                        <div>
                            <p class="text-xs font-bold {{ $enabled ? '' : 'line-through text-slate-500' }}">{{ $assignee->displayName() }}</p>
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-muted">{{ $enabled ? 'Enabled' : 'Disabled' }}</p>
                        </div>
                        <form method="POST" action="{{ route('tasks.assignees.toggle', [$task, $assignee]) }}">
                            @csrf
                            @method('PATCH')
                            <button class="rounded-lg px-2 py-1 text-[10px] font-bold {{ $enabled ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                                {{ $enabled ? 'Disable' : 'Enable' }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <form id="delete-task" method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return requestDeleteConfirm(event, { title: 'Delete this task?', message: 'This task will be permanently removed.' })" class="hidden">
        @csrf
        @method('DELETE')
    </form>
@endsection
