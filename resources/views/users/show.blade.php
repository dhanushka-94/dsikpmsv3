@extends('layouts.app')

@section('title', $user->displayName())
@section('page-title', $user->displayName())
@section('page-subtitle', 'Profile & progress')

@section('actions')
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('users.tree') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Back</a>
    @if($canManage)
        <a href="{{ route('users.edit', $user) }}" class="rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-700">Edit</a>
    @endif
@endsection

@section('content')
    <div
        x-data="userAssignmentsModal()"
        @open-user-assignments="open($event.detail)"
        @keydown.escape.window="visible && close()"
        class="space-y-6"
    >
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
                    @if($user->profilePictureUrl())
                        <img src="{{ $user->profilePictureUrl() }}" class="h-28 w-28 rounded-3xl object-cover ring-4 ring-brand-50" alt="">
                    @else
                        <div class="flex h-28 w-28 items-center justify-center rounded-3xl bg-brand-50 text-4xl font-extrabold text-brand-600">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    @endif
                    <dl class="grid flex-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wider text-muted">Title</dt>
                            <dd class="mt-1 font-semibold">{{ $user->title?->value ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wider text-muted">EPF Number</dt>
                            <dd class="mt-1 font-semibold">{{ $user->epf_number ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wider text-muted">Department</dt>
                            <dd class="mt-1 font-semibold">{{ $user->department?->displayName() ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wider text-muted">Designation</dt>
                            <dd class="mt-1 font-semibold">{{ $user->designation?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wider text-muted">Role</dt>
                            <dd class="mt-1 font-semibold">{{ $user->role->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wider text-muted">Reports to</dt>
                            <dd class="mt-1 font-semibold">{{ $user->parent?->displayName() ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wider text-muted">Status</dt>
                            <dd class="mt-1">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </dd>
                        </div>
                        @if($canManage)
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wider text-muted">Email</dt>
                                <dd class="mt-1 font-semibold">{{ $user->email }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-bold">Quick counts</h3>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @unless($user->isSuperAdmin())
                            @include('users.partials.assignment-count-badges', ['user' => $user])
                        @else
                            <p class="text-sm text-muted">No project or task assignments.</p>
                        @endunless
                    </div>
                </div>

                @if($canManage)
                    @if(auth()->user()->canResetPasswords())
                        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h3 class="font-bold">Reset password</h3>
                            <form method="POST" action="{{ route('users.reset-password', $user) }}" class="mt-4 space-y-3">
                                @csrf
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="send_email" value="1" class="rounded text-brand-600 focus:ring-brand-500">
                                    Send new password by email
                                </label>
                                <button class="w-full rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-700">
                                    Reset & show credentials
                                </button>
                            </form>
                        </div>
                    @endif

                    <a href="{{ route('activity-logs.user', $user) }}" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-center text-sm font-bold text-slate-700 shadow-sm hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700">
                        View user activity
                    </a>

                    <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return requestDeleteConfirm(event, { title: 'Delete this user?', message: 'This user account will be permanently removed.' })">
                        @csrf
                        @method('DELETE')
                        <button class="w-full rounded-2xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-bold text-red-700 hover:bg-red-100">
                            Delete user
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @unless($user->isSuperAdmin())
            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-base font-bold">Project progress</h2>
                        <span class="text-2xl font-extrabold text-ink">{{ $projectStats['percent'] }}%</span>
                    </div>
                    <p class="mt-1 text-sm text-muted">{{ $projectStats['completed'] }} of {{ $projectStats['total'] }} projects completed</p>
                    <div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $projectStats['percent'] }}%"></div>
                    </div>
                    <div class="mt-4 grid grid-cols-3 gap-2 text-center text-xs">
                        <div class="rounded-2xl bg-sky-50 px-2 py-3">
                            <p class="text-lg font-extrabold text-sky-700">{{ $projectStats['ongoing'] }}</p>
                            <p class="font-semibold text-sky-700/80">Ongoing</p>
                        </div>
                        <div class="rounded-2xl bg-amber-50 px-2 py-3">
                            <p class="text-lg font-extrabold text-amber-700">{{ $projectStats['on_hold'] }}</p>
                            <p class="font-semibold text-amber-700/80">On hold</p>
                        </div>
                        <div class="rounded-2xl bg-emerald-50 px-2 py-3">
                            <p class="text-lg font-extrabold text-emerald-700">{{ $projectStats['completed'] }}</p>
                            <p class="font-semibold text-emerald-700/80">Completed</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-base font-bold">Task progress</h2>
                        <span class="text-2xl font-extrabold text-brand-600">{{ $taskStats['percent'] }}%</span>
                    </div>
                    <p class="mt-1 text-sm text-muted">{{ $taskStats['done'] }} of {{ $taskStats['total'] }} tasks done</p>
                    <div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-brand-600 transition-all" style="width: {{ $taskStats['percent'] }}%"></div>
                    </div>
                    <div class="mt-4 grid grid-cols-4 gap-2 text-center text-xs">
                        <div class="rounded-2xl bg-slate-50 px-2 py-3">
                            <p class="text-lg font-extrabold text-slate-700">{{ $taskStats['todo'] }}</p>
                            <p class="font-semibold text-slate-600">To do</p>
                        </div>
                        <div class="rounded-2xl bg-sky-50 px-2 py-3">
                            <p class="text-lg font-extrabold text-sky-700">{{ $taskStats['in_progress'] }}</p>
                            <p class="font-semibold text-sky-700/80">In progress</p>
                        </div>
                        <div class="rounded-2xl bg-violet-50 px-2 py-3">
                            <p class="text-lg font-extrabold text-violet-700">{{ $taskStats['review'] }}</p>
                            <p class="font-semibold text-violet-700/80">Review</p>
                        </div>
                        <div class="rounded-2xl bg-emerald-50 px-2 py-3">
                            <p class="text-lg font-extrabold text-emerald-700">{{ $taskStats['done'] }}</p>
                            <p class="font-semibold text-emerald-700/80">Done</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-bold">Assigned projects</h2>
                    <div class="mt-4 divide-y divide-slate-100">
                        @forelse($projects as $project)
                            <a href="{{ route('projects.show', $project) }}" class="flex items-start justify-between gap-3 py-3 transition hover:bg-slate-50/80">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold">{{ $project->name }}</p>
                                    <p class="mt-0.5 text-xs text-muted">
                                        {{ $project->year }}
                                        @if($project->reference_number) · {{ $project->reference_number }} @endif
                                        · {{ \App\Enums\ProjectPermission::from($project->pivot->permission)->label() }}
                                    </p>
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $project->status->badgeClasses() }}">{{ $project->status->label() }}</span>
                            </a>
                        @empty
                            <p class="py-8 text-center text-sm text-muted">No projects assigned.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-bold">Assigned tasks</h2>
                    <div class="mt-4 divide-y divide-slate-100">
                        @forelse($tasks as $task)
                            <a href="{{ $task->project ? route('projects.tasks.board', $task->project) : '#' }}" class="flex items-start justify-between gap-3 py-3 transition hover:bg-slate-50/80">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold">{{ $task->title }}</p>
                                    <p class="mt-0.5 text-xs text-muted">
                                        {{ $task->project?->name ?? '—' }}
                                        · {{ $task->priority->label() }}
                                        · {{ $task->starts_at->format('Y-m-d') }} → {{ $task->ends_at->format('Y-m-d') }}
                                    </p>
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $task->status->badgeClasses() }}">{{ $task->status->label() }}</span>
                            </a>
                        @empty
                            <p class="py-8 text-center text-sm text-muted">No tasks assigned.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endunless

        @include('users.partials.assignments-modal')
    </div>
@endsection
