@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Welcome back, '.$user->displayName())

@section('actions')
    <a href="{{ route('projects.index') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Projects</a>
    <a href="{{ route('users.tree') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Users tree</a>
    @if($isAdmin)
        <a href="{{ route('kpis.index') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">KPIs</a>
        <a href="{{ route('kpis.create') }}" class="rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-700">Add KPI</a>
    @endif
@endsection

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">My projects</p>
            <p class="mt-2 text-3xl font-extrabold text-ink">{{ $projectStats['total'] }}</p>
            <p class="mt-1 text-xs font-semibold text-muted">{{ $projectStats['ongoing'] }} ongoing · {{ $projectStats['completed'] }} completed</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">My tasks</p>
            <p class="mt-2 text-3xl font-extrabold text-brand-600">{{ $taskStats['total'] }}</p>
            <p class="mt-1 text-xs font-semibold text-muted">{{ $taskStats['done'] }} done · {{ $taskStats['in_progress'] }} in progress</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">My KPI weightage</p>
            <p class="mt-2 text-3xl font-extrabold text-violet-700">{{ $kpiStats['weightage_used'] }}%</p>
            <p class="mt-1 text-xs font-semibold text-muted">{{ $kpiStats['assigned'] }} assigned · {{ $kpiStats['weightage_remaining'] }}% free</p>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-violet-500" style="width: {{ min(100, $kpiStats['weightage_used']) }}%"></div>
            </div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Task progress</p>
            <p class="mt-2 text-3xl font-extrabold text-sky-600">{{ $taskStats['percent'] }}%</p>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-sky-500" style="width: {{ $taskStats['percent'] }}%"></div>
            </div>
            <p class="mt-2 text-xs font-semibold text-muted">Project progress {{ $projectStats['percent'] }}%</p>
        </div>
    </div>

    @if($isAdmin && $systemStats && $kpiOverview)
        <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
            <div class="rounded-3xl border border-brand-100 bg-brand-50/70 p-4 shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-wider text-brand-700/70">System users</p>
                <p class="mt-1 text-2xl font-extrabold text-brand-700">{{ $systemStats['users'] }}</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">All projects</p>
                <p class="mt-1 text-2xl font-extrabold">{{ $systemStats['projects'] }}</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Open tasks</p>
                <p class="mt-1 text-2xl font-extrabold text-amber-700">{{ $systemStats['open_tasks'] }}</p>
            </div>
            <div class="rounded-3xl border border-violet-100 bg-violet-50/70 p-4 shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-wider text-violet-700/70">KPIs</p>
                <p class="mt-1 text-2xl font-extrabold text-violet-700">{{ $kpiOverview['total'] }}</p>
                <p class="text-[11px] font-semibold text-violet-700/80">{{ $kpiOverview['active'] }} active</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">KPI categories</p>
                <p class="mt-1 text-2xl font-extrabold">{{ $kpiOverview['categories'] }}</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Feeds this month</p>
                <p class="mt-1 text-2xl font-extrabold text-emerald-700">{{ $kpiOverview['results_month'] }}</p>
                <p class="text-[11px] font-semibold text-muted">{{ $kpiOverview['results'] }} total history</p>
            </div>
        </div>
    @endif

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('projects.index') }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
            <p class="text-sm font-extrabold">Projects</p>
            <p class="mt-1 text-xs text-muted">Browse projects, refs, dates, and teams</p>
        </a>
        @if($isAdmin)
            <a href="{{ route('kpis.index') }}" class="rounded-3xl border border-violet-100 bg-violet-50/50 p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-violet-300 hover:shadow-md">
                <p class="text-sm font-extrabold text-violet-800">KPIs</p>
                <p class="mt-1 text-xs text-violet-700/80">Formulas, quick feed, charts & history</p>
            </a>
            <a href="{{ route('kpi-categories.index') }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
                <p class="text-sm font-extrabold">KPI categories</p>
                <p class="mt-1 text-xs text-muted">Organize and manage KPI groups</p>
            </a>
            <a href="{{ route('activity-logs.index') }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
                <p class="text-sm font-extrabold">Activity logs</p>
                <p class="mt-1 text-xs text-muted">Audit trail including KPI feeds & changes</p>
            </a>
        @else
            <a href="{{ route('users.tree') }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
                <p class="text-sm font-extrabold">Users tree</p>
                <p class="mt-1 text-xs text-muted">Org chart with project & task counts</p>
            </a>
            <a href="{{ route('users.profile', $user) }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
                <p class="text-sm font-extrabold">My progress</p>
                <p class="mt-1 text-xs text-muted">Profile with project and task progress</p>
            </a>
            <a href="{{ route('profile.edit') }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
                <p class="text-sm font-extrabold">My profile</p>
                <p class="mt-1 text-xs text-muted">Update your personal details</p>
            </a>
        @endif
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-bold">My projects</h2>
                <a href="{{ route('projects.index') }}" class="text-xs font-bold text-brand-700 hover:underline">View all</a>
            </div>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse($recentProjects as $project)
                    <a href="{{ route('projects.show', $project) }}" class="flex items-start justify-between gap-3 py-3 transition hover:bg-slate-50/80">
                        <div class="min-w-0">
                            <x-long-text :text="$project->name" :lines="2" class="font-semibold" />
                            <p class="mt-0.5 text-xs text-muted">
                                {{ $project->year }}
                                @if($project->reference_number) · {{ $project->reference_number }} @endif
                                · {{ $project->tasks_count }} {{ $project->tasks_count === 1 ? 'task' : 'tasks' }}
                            </p>
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-1">
                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $project->status->badgeClasses() }}">{{ $project->status->label() }}</span>
                            <span class="text-[10px] font-semibold text-muted">Board / Gantt</span>
                        </div>
                    </a>
                @empty
                    <p class="py-8 text-center text-sm text-muted">No projects assigned yet.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-bold">Upcoming tasks</h2>
                <a href="{{ route('users.profile', $user) }}" class="text-xs font-bold text-brand-700 hover:underline">My progress</a>
            </div>

            <div class="mb-4 mt-4 grid grid-cols-4 gap-2 text-center text-[11px]">
                <div class="rounded-2xl bg-slate-50 px-2 py-2">
                    <p class="text-base font-extrabold">{{ $taskStats['todo'] }}</p>
                    <p class="font-semibold text-muted">To do</p>
                </div>
                <div class="rounded-2xl bg-sky-50 px-2 py-2">
                    <p class="text-base font-extrabold text-sky-700">{{ $taskStats['in_progress'] }}</p>
                    <p class="font-semibold text-sky-700/80">Doing</p>
                </div>
                <div class="rounded-2xl bg-violet-50 px-2 py-2">
                    <p class="text-base font-extrabold text-violet-700">{{ $taskStats['review'] }}</p>
                    <p class="font-semibold text-violet-700/80">Review</p>
                </div>
                <div class="rounded-2xl bg-emerald-50 px-2 py-2">
                    <p class="text-base font-extrabold text-emerald-700">{{ $taskStats['done'] }}</p>
                    <p class="font-semibold text-emerald-700/80">Done</p>
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($upcomingTasks as $task)
                    <a href="{{ $task->project ? route('projects.tasks.board', $task->project) : '#' }}" class="flex items-start justify-between gap-3 py-3 transition hover:bg-slate-50/80">
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ $task->title }}</p>
                            <p class="mt-0.5 text-xs text-muted">
                                {{ $task->project?->name ?? '—' }}
                                · due {{ dsi_datetime($task->ends_at) }}
                            </p>
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-1">
                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $task->status->badgeClasses() }}">{{ $task->status->label() }}</span>
                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $task->priority->badgeClasses() }}">{{ $task->priority->label() }}</span>
                        </div>
                    </a>
                @empty
                    <p class="py-6 text-center text-sm text-muted">No open tasks right now.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-bold">{{ $isAdmin ? 'Recent KPIs' : 'My KPIs' }}</h2>
                @if($isAdmin)
                    <a href="{{ route('kpis.index') }}" class="text-xs font-bold text-brand-700 hover:underline">Manage KPIs</a>
                @endif
            </div>

            <div class="mt-4 divide-y divide-slate-100">
                @php $kpiList = $isAdmin ? $recentKpis : $myRecentKpis; @endphp
                @forelse($kpiList as $kpi)
                    <div class="flex items-start justify-between gap-3 py-3">
                        <div class="min-w-0">
                            @if($isAdmin)
                                <x-long-text :text="$kpi->name" :href="route('kpis.show', $kpi)" :lines="2" class="font-semibold hover:text-brand-700" />
                            @else
                                <x-long-text :text="$kpi->name" :lines="2" class="font-semibold" />
                            @endif
                            <p class="mt-0.5 break-words text-xs text-muted [overflow-wrap:anywhere]" title="{{ $kpi->formula }}">
                                {{ $kpi->category?->name ?? '—' }}
                                · {{ $kpi->formula }}
                                @if($isAdmin)
                                    · {{ $kpi->results_count }} {{ $kpi->results_count === 1 ? 'feed' : 'feeds' }}
                                @else
                                    · weightage {{ $kpi->pivot->weightage }}%
                                @endif
                            </p>
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-1">
                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $kpi->benchmark_type->badgeClasses() }}">{{ $kpi->benchmark_type->label() }} {{ $kpi->benchmark_percent }}%</span>
                            <span class="text-[11px] font-bold text-brand-700">{{ $kpi->formula_result ?? '—' }}</span>
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-muted">
                        {{ $isAdmin ? 'No KPIs created yet.' : 'No KPIs assigned to you yet.' }}
                    </p>
                @endforelse
            </div>

            @if($isAdmin)
                <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                    <a href="{{ route('kpis.create') }}" class="rounded-xl bg-brand-600 px-3 py-1.5 text-[11px] font-bold text-white hover:bg-brand-700">Add KPI</a>
                    <a href="{{ route('kpis.index') }}" class="rounded-xl border border-slate-200 px-3 py-1.5 text-[11px] font-semibold text-slate-600 hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700">Quick feed</a>
                    <a href="{{ route('kpi-categories.index') }}" class="rounded-xl border border-slate-200 px-3 py-1.5 text-[11px] font-semibold text-slate-600 hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700">Categories</a>
                </div>
            @endif
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-bold">{{ $isAdmin ? 'Latest KPI feeds' : 'KPI weightage summary' }}</h2>
                @if($isAdmin)
                    <a href="{{ route('kpis.index') }}" class="text-xs font-bold text-brand-700 hover:underline">Open KPIs</a>
                @endif
            </div>

            @if($isAdmin)
                <div class="mt-4 divide-y divide-slate-100">
                    @forelse($recentKpiResults as $entry)
                        <a href="{{ route('kpis.show', $entry->kpi) }}" class="flex items-start justify-between gap-3 py-3 transition hover:bg-slate-50/80">
                            <div class="min-w-0">
                                <x-long-text :text="$entry->kpi?->name ?? 'KPI'" :lines="2" class="font-semibold" />
                                <p class="mt-0.5 break-words text-xs text-muted [overflow-wrap:anywhere]">
                                    {{ $entry->recorded_on->format('Y-m-d') }}
                                    · {{ $entry->creator?->displayName() ?? '—' }}
                                    @foreach(($entry->values ?? []) as $valueName => $value)
                                        · {{ $valueName }}: {{ $value }}
                                    @endforeach
                                </p>
                            </div>
                            <span class="shrink-0 text-sm font-extrabold text-brand-700">{{ $entry->result }}</span>
                        </a>
                    @empty
                        <p class="py-8 text-center text-sm text-muted">No KPI data feeds yet. Use Quick feed from the KPIs page.</p>
                    @endforelse
                </div>
            @else
                <div class="mt-4 space-y-3">
                    <div class="rounded-2xl bg-violet-50 px-4 py-3">
                        <p class="text-xs font-bold uppercase tracking-wider text-violet-700/70">Used</p>
                        <p class="text-2xl font-extrabold text-violet-700">{{ $kpiStats['weightage_used'] }}%</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3">
                        <p class="text-xs font-bold uppercase tracking-wider text-muted">Remaining</p>
                        <p class="text-2xl font-extrabold">{{ $kpiStats['weightage_remaining'] }}%</p>
                    </div>
                    <p class="text-xs text-muted">Each person has 100% total weightage across all assigned KPIs.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="text-base font-bold">Your profile</h2>
            <div class="mt-5 flex flex-col gap-5 sm:flex-row sm:items-center">
                @if($user->profilePictureUrl())
                    <img src="{{ $user->profilePictureUrl() }}" alt="" class="h-24 w-24 rounded-3xl object-cover ring-4 ring-brand-50">
                @else
                    <div class="flex h-24 w-24 items-center justify-center rounded-3xl bg-brand-50 text-3xl font-extrabold text-brand-600">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
                <dl class="grid flex-1 gap-3 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Full name</dt>
                        <dd class="mt-1 font-semibold">{{ $user->displayName() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Role</dt>
                        <dd class="mt-1 font-semibold">{{ $user->role->label() }}</dd>
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
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Reports to</dt>
                        <dd class="mt-1 font-semibold">{{ $user->parent?->displayName() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">KPI weightage</dt>
                        <dd class="mt-1 font-semibold">{{ $kpiStats['weightage_used'] }}% used · {{ $kpiStats['assigned'] }} KPIs</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="rounded-3xl bg-gradient-to-br from-brand-600 to-brand-800 p-6 text-white shadow-lg shadow-brand-600/20">
            <p class="text-sm font-semibold text-brand-100">DSI KPI Monitoring</p>
            <h3 class="mt-2 text-2xl font-extrabold leading-tight">KPIs, projects & progress in one place.</h3>
            <p class="mt-3 text-sm text-brand-100/90">Build formula presets, feed monthly data, track charts against benchmark, and audit every change.</p>
            <div class="mt-6 flex flex-wrap gap-2">
                @if($isAdmin)
                    <a href="{{ route('kpis.index') }}" class="rounded-2xl bg-white px-4 py-2.5 text-sm font-bold text-brand-700 hover:bg-brand-50">Open KPIs</a>
                    <a href="{{ route('projects.index') }}" class="rounded-2xl border border-white/30 px-4 py-2.5 text-sm font-bold text-white hover:bg-white/10">Projects</a>
                @else
                    <a href="{{ route('projects.index') }}" class="rounded-2xl bg-white px-4 py-2.5 text-sm font-bold text-brand-700 hover:bg-brand-50">Open projects</a>
                    <a href="{{ route('users.tree') }}" class="rounded-2xl border border-white/30 px-4 py-2.5 text-sm font-bold text-white hover:bg-white/10">Users tree</a>
                @endif
            </div>
        </div>
    </div>

    @if($isAdmin && $recentActivity->isNotEmpty())
        <div class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-bold">Recent activity</h2>
                <a href="{{ route('activity-logs.index') }}" class="text-xs font-bold text-brand-700 hover:underline">View all logs</a>
            </div>
            <div class="mt-4 divide-y divide-slate-100">
                @foreach($recentActivity as $log)
                    <div class="flex flex-col gap-1 py-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold">{{ $log->description }}</p>
                            <p class="mt-0.5 text-xs text-muted">
                                {{ $log->user?->displayName() ?? 'System' }}
                                · {{ $log->moduleLabel() }}
                                · {{ $log->actionLabel() }}
                            </p>
                        </div>
                        <p class="shrink-0 text-xs font-semibold text-muted">{{ dsi_datetime($log->created_at) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection
