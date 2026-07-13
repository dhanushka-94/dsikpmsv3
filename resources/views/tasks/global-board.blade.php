@extends('layouts.app')

@section('title', 'Task board')
@section('page-title', 'Task board')
@section('page-subtitle', 'Kanban view across your projects')

@section('actions')
    <a href="{{ route('tasks.index') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Task list</a>
    @if(auth()->user()->canManageUsers() || $projects->contains(fn ($p) => $p->canBeEditedBy(auth()->user())))
        <a href="{{ route('tasks.create') }}" class="rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-700">Add task</a>
    @endif
@endsection

@section('content')
    <form method="GET" class="mb-5 flex flex-wrap items-end gap-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="min-w-[14rem] flex-1">
            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">Project</label>
            <select name="project_id" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm">
                <option value="">All projects</option>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}" @selected((string) $selectedProjectId === (string) $project->id)>{{ $project->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="rounded-2xl bg-ink px-4 py-2.5 text-sm font-bold text-white">Filter</button>
        @if($selectedProjectId)
            <a href="{{ route('tasks.board') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Reset</a>
        @endif
    </form>

    <p class="mb-4 text-sm text-muted">Drag cards between columns to update status.</p>

    <div
        class="flex gap-4 overflow-x-auto pb-4"
        x-data="taskBoard(@js(route('tasks.status', ['task' => '__ID__'])), @js(csrf_token()), @js($canDrag))"
        x-init="init()"
    >
        @foreach(\App\Enums\TaskStatus::boardColumns() as $status)
            @php $columnTasks = $columns[$status->value]; @endphp
            <div class="w-80 shrink-0 rounded-3xl border border-slate-200 bg-slate-50/80">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <div>
                        <h2 class="text-sm font-extrabold">{{ $status->label() }}</h2>
                        <p class="text-xs text-muted">{{ $columnTasks->count() }} {{ $columnTasks->count() === 1 ? 'task' : 'tasks' }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $status->badgeClasses() }}">{{ strtoupper($status->value) }}</span>
                </div>

                <div
                    class="task-column min-h-[24rem] space-y-3 p-3"
                    data-status="{{ $status->value }}"
                >
                    @forelse($columnTasks as $task)
                        @php $canEdit = $task->project?->canBeEditedBy($user); @endphp
                        <article
                            class="task-card cursor-grab rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-brand-200 hover:shadow-md active:cursor-grabbing"
                            data-id="{{ $task->id }}"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="text-sm font-bold leading-snug text-ink">{{ $task->title }}</h3>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $task->priority->badgeClasses() }}">{{ $task->priority->label() }}</span>
                            </div>
                            @if($task->project)
                                <a href="{{ route('projects.tasks.board', $task->project) }}" class="mt-2 block break-words text-xs font-semibold text-brand-700 [overflow-wrap:anywhere] hover:underline" title="{{ $task->project->name }}" @click.stop>
                                    {{ $task->project->name }}
                                </a>
                            @endif
                            @if($task->assignees->isNotEmpty())
                                <div class="mt-2 space-y-1">
                                    @foreach($task->assignees as $assignee)
                                        @php $enabled = (bool) $assignee->pivot->is_enabled; @endphp
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="truncate text-xs {{ $enabled ? 'text-muted' : 'text-slate-400 line-through' }}">{{ $assignee->displayName() }}</p>
                                            @if($canEdit)
                                                <form method="POST" action="{{ route('tasks.assignees.toggle', [$task, $assignee]) }}" @click.stop>
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="rounded px-1.5 py-0.5 text-[9px] font-bold {{ $enabled ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                                                        {{ $enabled ? 'Off' : 'On' }}
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-[11px] font-semibold text-slate-500">
                                <span>{{ dsi_datetime_short($task->starts_at) }} → {{ dsi_datetime_short($task->ends_at) }}</span>
                                @if($canEdit)
                                    <a href="{{ route('tasks.edit', $task) }}" class="text-brand-700 hover:underline" @click.stop>Edit</a>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="px-2 py-8 text-center text-xs text-muted">No tasks</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
    function taskBoard(statusUrlTemplate, csrfToken, canManage) {
        return {
            init() {
                if (!canManage || typeof Sortable === 'undefined') return;

                this.$el.querySelectorAll('.task-column').forEach((column) => {
                    Sortable.create(column, {
                        group: 'tasks',
                        animation: 180,
                        ghostClass: 'opacity-40',
                        onAdd: (evt) => this.persist(evt),
                        onUpdate: (evt) => this.persist(evt),
                    });
                });
            },
            persist(evt) {
                const card = evt.item;
                const status = evt.to.dataset.status;
                const taskId = card.dataset.id;
                const sortOrder = Array.from(evt.to.children).indexOf(card);
                const url = statusUrlTemplate.replace('__ID__', taskId);

                fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ status, sort_order: sortOrder }),
                }).catch(() => {
                    window.location.reload();
                });
            }
        }
    }
</script>
@endpush
