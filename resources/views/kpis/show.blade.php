@extends('layouts.app')

@section('title', $kpi->name)
@section('page-title', $kpi->name)
@section('page-subtitle', 'KPI details')

@section('actions')
    <a href="{{ route('kpis.index') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Back</a>
    @if($canManage ?? false)
        <a href="{{ route('kpis.edit', $kpi) }}" class="rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-700">Edit</a>
    @endif
@endsection

@section('content')
    @php
        $formulaVariables = $kpi->formulaVariables();
        $savedValues = $kpi->formula_values ?? [];
        $initialValues = collect($formulaVariables)
            ->mapWithKeys(fn ($variable) => [$variable => old('values.'.$variable, $savedValues[$variable] ?? '')])
            ->all();
    @endphp

    <div class="mb-5 flex flex-wrap items-center gap-2">
        <span class="rounded-full bg-ink px-2.5 py-1 text-xs font-bold text-white">{{ $kpi->kpi_index ?: 'No index' }}</span>
        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ $kpi->category?->name ?? '—' }}</span>
        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $kpi->benchmark_type->badgeClasses() }}">{{ $kpi->benchmark_type->label() }}</span>
        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $kpi->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
            {{ $kpi->is_active ? 'Active' : 'Inactive' }}
        </span>
    </div>

    <form method="GET" action="{{ route('kpis.show', $kpi) }}" class="mb-6 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <p class="text-sm font-bold">Date range</p>
                <p class="mt-0.5 text-xs text-muted">Filters chart, stats, and results history.</p>
            </div>
            <div class="min-w-[10rem] flex-1 sm:flex-none">
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">From</label>
                <input
                    type="date"
                    name="date_from"
                    value="{{ $dateFrom }}"
                    min="{{ optional($kpi->start_date)->format('Y-m-d') }}"
                    max="{{ optional($kpi->end_date)->format('Y-m-d') }}"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100"
                >
            </div>
            <div class="min-w-[10rem] flex-1 sm:flex-none">
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">To</label>
                <input
                    type="date"
                    name="date_to"
                    value="{{ $dateTo }}"
                    min="{{ optional($kpi->start_date)->format('Y-m-d') }}"
                    max="{{ optional($kpi->end_date)->format('Y-m-d') }}"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100"
                >
            </div>
            <button type="submit" class="rounded-2xl bg-ink px-4 py-2.5 text-sm font-bold text-white">Apply</button>
            @if($hasDateFilter ?? false)
                <a href="{{ route('kpis.show', $kpi) }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Reset</a>
            @endif
        </div>

        @if(!empty($financialYears))
            <div class="mt-4 border-t border-slate-100 pt-3">
                <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-muted">Financial year (1 Apr → 31 Mar)</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($financialYears as $fy)
                        <a
                            href="{{ route('kpis.show', ['kpi' => $kpi, 'date_from' => $fy['from'], 'date_to' => $fy['to']]) }}"
                            class="rounded-full px-3 py-1.5 text-xs font-bold transition {{ $fy['active'] ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-slate-50 text-slate-700 hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700' }}"
                            title="{{ $fy['from'] }} → {{ $fy['to'] }}"
                        >
                            {{ $fy['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if($hasDateFilter ?? false)
            <p class="mt-3 text-xs font-semibold text-brand-700">
                Showing
                {{ $dateFrom ?: optional($kpi->start_date)->format('Y-m-d') }}
                →
                {{ $dateTo ?: optional($kpi->end_date)->format('Y-m-d') }}
            </p>
        @endif
        @error('date_from')
            <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
        @error('date_to')
            <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </form>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div
                class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                x-data="kpiValueRunner(@js($kpi->formula), @js($initialValues))"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold">Feed data</h2>
                        <p class="mt-1 text-sm text-muted">Select a date, enter values, and save — all results are kept in history.</p>
                    </div>
                    <div class="rounded-2xl bg-brand-50 px-4 py-2 text-right">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-brand-700/70">Result</p>
                        <p class="text-2xl font-extrabold text-brand-700" x-text="resultLabel"></p>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Formula preset</p>
                    <p class="mt-1 break-words font-mono text-sm font-semibold [overflow-wrap:anywhere]" title="{{ $kpi->formula }}">{{ $kpi->formula }}</p>
                </div>

                <form method="POST" action="{{ route('kpis.calculate', $kpi) }}" class="mt-5 space-y-4">
                    @csrf

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">Date <span class="text-brand-600">*</span></label>
                        <input
                            type="date"
                            name="recorded_on"
                            value="{{ old('recorded_on', now()->toDateString()) }}"
                            required
                            min="{{ optional($kpi->start_date)->format('Y-m-d') }}"
                            max="{{ optional($kpi->end_date)->format('Y-m-d') }}"
                            class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100 sm:max-w-xs"
                        >
                        <p class="mt-1 text-xs text-muted">Each save adds a new history record (same date allowed).</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach($formulaVariables as $variable)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <label class="block">
                                    <span class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-muted">Value name</span>
                                    <span class="mb-2 block break-words text-sm font-bold [overflow-wrap:anywhere]" title="{{ $variable }}">{{ $variable }}</span>
                                    <span class="mb-1.5 block text-xs font-semibold text-slate-600">Value</span>
                                    <input
                                        type="number"
                                        step="any"
                                        required
                                        name="values[{{ $variable }}]"
                                        value="{{ old('values.'.$variable, $initialValues[$variable] ?? '') }}"
                                        x-model="values[{{ json_encode($variable) }}]"
                                        @input="error = ''"
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100"
                                    >
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <p class="text-xs font-semibold text-red-600" x-show="error" x-text="error"></p>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white hover:bg-brand-700">
                            Save to history
                        </button>
                        <button type="button" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600" @click="clearValues()">
                            Clear values
                        </button>
                    </div>
                </form>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold">KPI progress chart</h2>
                        <p class="mt-1 text-sm text-muted">
                            Monthly Progress vs Expected Progress vs Benchmark
                            · Range <span class="font-semibold text-ink">{{ $chartData['rangeLabel'] ?? ($kpi->start_date->format('M Y').' → '.$kpi->end_date->format('M Y')) }}</span>
                        </p>
                    </div>
                    @if($progressStats['trend'] !== 'stable' && $progressStats['latest'] !== null)
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $progressStats['trend'] === 'up' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                            Trend {{ $progressStats['trend'] === 'up' ? '↑ up' : '↓ down' }}
                        </span>
                    @endif
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Last saved</p>
                        <p class="mt-1 text-xl font-extrabold text-brand-700">{{ $progressStats['latest'] ?? '—' }}</p>
                        @if(!empty($progressStats['latest_date']))
                            <p class="mt-0.5 text-[11px] font-semibold text-muted">As of {{ $progressStats['latest_date'] }}</p>
                        @endif
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Average</p>
                        <p class="mt-1 text-xl font-extrabold">{{ $progressStats['average'] ?? '—' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Min / Max</p>
                        <p class="mt-1 text-xl font-extrabold">{{ $progressStats['min'] ?? '—' }} <span class="text-sm font-semibold text-muted">/</span> {{ $progressStats['max'] ?? '—' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Vs benchmark</p>
                        <p class="mt-1 text-xl font-extrabold {{ ($progressStats['vs_benchmark'] ?? 0) >= 0 ? 'text-emerald-700' : 'text-amber-700' }}">
                            @if($progressStats['vs_benchmark'] === null)
                                —
                            @else
                                {{ $progressStats['vs_benchmark'] > 0 ? '+' : '' }}{{ $progressStats['vs_benchmark'] }}
                            @endif
                        </p>
                    </div>
                </div>

                @if($progressStats['progress_percent'] !== null)
                    <div class="mt-5">
                        <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                            <span class="font-semibold">Progress to benchmark</span>
                            <span class="font-bold text-brand-700">{{ $progressStats['progress_percent'] }}%</span>
                        </div>
                        <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-brand-600 transition-all" style="width: {{ min(100, $progressStats['progress_percent']) }}%"></div>
                        </div>
                        <p class="mt-2 text-xs text-muted">
                            Benchmark target: {{ $kpi->benchmark_percent }}% ({{ $kpi->benchmark_type->label() }})
                        </p>
                    </div>
                @endif

                <div class="mt-6">
                    @if(count($chartData['labels']))
                        <div class="relative h-80 w-full">
                            <canvas id="kpiProgressChart"></canvas>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-4 text-xs text-muted">
                            <span><span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-brand-600"></span>Monthly Progress (actual monthly average)</span>
                            <span><span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-sky-500"></span>Expected Progress (planned path to target)</span>
                            <span><span class="mr-1 inline-block h-0.5 w-4 align-middle border-t-2 border-dashed border-slate-500"></span>Benchmark ({{ $chartData['benchmarkValue'] }}%)</span>
                        </div>
                        @unless($chartData['hasActuals'])
                            <p class="mt-2 text-xs text-amber-700">No monthly actuals yet — expected and benchmark lines are shown for the full period.</p>
                        @endunless
                    @else
                        <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-14 text-center text-sm text-muted">
                            Set KPI start/end dates to see the progress chart.
                        </div>
                    @endif
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold">Results history</h2>
                        <p class="mt-1 text-sm text-muted">
                            @if($hasDateFilter ?? false)
                                Saved results in the selected date range (newest save first).
                            @else
                                All saved results for this KPI (newest save first).
                            @endif
                        </p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $historyResults->total() }} entries</span>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-muted">
                            <tr>
                                <th class="px-3 py-3">Date</th>
                                <th class="px-3 py-3">Values</th>
                                <th class="px-3 py-3">Result</th>
                                <th class="px-3 py-3">Saved by</th>
                                @if($canManage ?? false)
                                    <th class="px-3 py-3 text-right">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($historyResults as $entry)
                                @php $isNewest = $historyResults->onFirstPage() && $loop->first; @endphp
                                <tr class="{{ $isNewest ? 'bg-brand-50/40' : '' }}">
                                    <td class="px-3 py-3 font-semibold whitespace-nowrap">
                                        {{ $entry->recorded_on->format('Y-m-d') }}
                                        @if($isNewest)
                                            <span class="ml-1 rounded-full bg-brand-100 px-2 py-0.5 text-[10px] font-bold text-brand-700">Newest</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach(($entry->values ?? []) as $valueName => $value)
                                                <span class="max-w-full rounded-2xl bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700" title="{{ $valueName }}: {{ $value }}">
                                                    {{ short_formula_name((string) $valueName) }}: {{ $value }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="text-base font-extrabold text-brand-700">{{ $entry->result }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-muted whitespace-nowrap">
                                        {{ $entry->creator?->displayName() ?? '—' }}
                                        <div class="text-[11px]">{{ dsi_datetime($entry->created_at) }}</div>
                                    </td>
                                    @if($canManage ?? false)
                                        <td class="px-3 py-3 text-right">
                                            <form method="POST" action="{{ route('kpis.results.destroy', [$kpi, $entry]) }}" onsubmit="return requestDeleteConfirm(event, { title: 'Delete this result?', message: 'This history entry will be permanently removed.' })">
                                                @csrf
                                                @method('DELETE')
                                                <button class="rounded-xl border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700">Delete</button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ ($canManage ?? false) ? 5 : 4 }}" class="px-3 py-10 text-center text-muted">
                                        @if($hasDateFilter ?? false)
                                            No results in this date range.
                                        @else
                                            No results saved yet. Pick a date, enter values, and save.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($historyResults->hasPages())
                    <div class="mt-4 border-t border-slate-100 pt-4">
                        {{ $historyResults->links() }}
                    </div>
                @endif
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold">Overview</h2>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Index</dt>
                        <dd class="mt-1 font-semibold">{{ $kpi->kpi_index ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Benchmark %</dt>
                        <dd class="mt-1 font-semibold">{{ $kpi->benchmark_percent }}%</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Period</dt>
                        <dd class="mt-1 font-semibold">{{ $kpi->start_date->format('Y-m-d') }} → {{ $kpi->end_date->format('Y-m-d') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Created by</dt>
                        <dd class="mt-1 font-semibold">{{ $kpi->creator?->displayName() ?? '—' }}</dd>
                    </div>
                </dl>
                @if($kpi->definition)
                    <div class="mt-5 border-t border-slate-100 pt-5">
                        <h3 class="text-sm font-bold">Definition</h3>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $kpi->definition }}</p>
                    </div>
                @endif
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold">Assigned projects</h2>
                <div class="mt-4 divide-y divide-slate-100">
                    @forelse($kpi->projects as $project)
                        <a href="{{ route('projects.show', $project) }}" class="flex items-center justify-between gap-3 py-3 hover:bg-slate-50/80">
                            <div class="min-w-0">
                                <x-long-text :text="$project->name" :lines="2" class="font-semibold" />
                                <p class="text-xs text-muted">{{ collect([$project->year, $project->company?->name, $project->plant?->name])->filter()->implode(' · ') }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $project->status->badgeClasses() }}">{{ $project->status->label() }}</span>
                        </a>
                    @empty
                        <p class="py-8 text-center text-sm text-muted">No projects assigned.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-bold">Progress summary</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Entries</dt>
                        <dd class="font-bold">{{ $progressStats['entries'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Last saved</dt>
                        <dd class="text-right">
                            <span class="font-bold text-brand-700">{{ $progressStats['latest'] ?? '—' }}</span>
                            @if(!empty($progressStats['latest_date']))
                                <div class="text-[11px] font-semibold text-muted">{{ $progressStats['latest_date'] }}</div>
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Benchmark</dt>
                        <dd class="font-bold">{{ $progressStats['benchmark'] }}%</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Progress</dt>
                        <dd class="font-bold">{{ $progressStats['progress_percent'] !== null ? $progressStats['progress_percent'].'%' : '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Expected now</dt>
                        <dd class="font-bold">{{ $progressStats['current_expected'] ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Trend</dt>
                        <dd class="font-bold capitalize">{{ $progressStats['trend'] }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-bold">Weightage users</h3>
                <p class="mt-1 text-xs text-muted">Mini progress for each assigned person on this KPI.</p>
                <div class="mt-4 space-y-3">
                    @forelse($userProgress as $row)
                        @php
                            $member = $row['user'];
                            $bar = $row['progress_percent'] !== null ? min(100, max(0, $row['progress_percent'])) : 0;
                        @endphp
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/80 px-3 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold">{{ $member->displayName() }}</p>
                                    <p class="truncate text-xs text-muted">{{ $member->designation?->name ?? '—' }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-700">{{ $row['weightage'] }}%</span>
                            </div>

                            <div class="mt-3">
                                <div class="mb-1 flex items-center justify-between gap-2 text-[11px]">
                                    <span class="font-semibold text-muted">Progress</span>
                                    <span class="font-bold text-brand-700">
                                        {{ $row['progress_percent'] !== null ? $row['progress_percent'].'%' : '—' }}
                                    </span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-200/80">
                                    <div class="h-full rounded-full bg-brand-600 transition-all" style="width: {{ $bar }}%"></div>
                                </div>
                                <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-[11px] text-muted">
                                    <span>
                                        Share
                                        <span class="font-bold text-ink">{{ $row['contribution'] !== null ? $row['contribution'].'%' : '—' }}</span>
                                        of {{ $row['weightage'] }}%
                                    </span>
                                    <span>
                                        Weighted
                                        <span class="font-bold text-ink">{{ $row['weighted_result'] ?? '—' }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-muted">No users assigned.</p>
                    @endforelse
                </div>
                @if($userProgress->isNotEmpty())
                    <p class="mt-4 text-xs font-semibold text-muted">
                        Total on this KPI: {{ $userProgress->sum('weightage') }}%
                    </p>
                @endif
            </div>

            @if(auth()->user()->isSuperAdmin())
                <form method="POST" action="{{ route('kpis.destroy', $kpi) }}" onsubmit="return requestDeleteConfirm(event, { title: 'Delete this KPI?', message: 'This will permanently delete the KPI, formula data, and result history. Solve the math check to continue.', requireMath: true })">
                    @csrf
                    @method('DELETE')
                    <button class="w-full rounded-2xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-bold text-red-700 hover:bg-red-100">
                        Delete KPI
                    </button>
                </form>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    function kpiValueRunner(formula, initialValues) {
        return {
            formula: formula || '',
            values: initialValues || {},
            error: '',
            get resultLabel() {
                const value = this.evaluate();
                return value === null ? '—' : value;
            },
            clearValues() {
                Object.keys(this.values).forEach((key) => {
                    this.values[key] = '';
                });
                this.error = '';
            },
            evaluate() {
                if (!this.formula.trim()) return null;

                try {
                    let normalized = this.formula;
                    const names = Object.keys(this.values);

                    for (const name of names) {
                        const raw = this.values[name];
                        if (raw === '' || raw === null || raw === undefined || isNaN(Number(raw))) {
                            return null;
                        }
                        normalized = normalized.split('{' + name + '}').join(String(Number(raw)));
                    }

                    if (/\{/.test(normalized)) {
                        this.error = 'Missing value for a formula field.';
                        return null;
                    }

                    normalized = normalized.replace(/(\d+(?:\.\d+)?)\s*%\s*(\d+(?:\.\d+)?)/g, '((($1/100)*$2))');
                    normalized = normalized.replace(/(\d+(?:\.\d+)?)\s*%/g, '($1/100)');

                    if (!/^[0-9+\-*/().\s]+$/.test(normalized)) {
                        this.error = 'Invalid formula';
                        return null;
                    }

                    // eslint-disable-next-line no-new-func
                    const value = Function('"use strict"; return (' + normalized + ')')();
                    if (typeof value !== 'number' || !isFinite(value)) {
                        this.error = 'Invalid result';
                        return null;
                    }

                    this.error = '';
                    return Math.round(value * 10000) / 10000;
                } catch (e) {
                    this.error = 'Invalid formula';
                    return null;
                }
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('kpiProgressChart');
        if (!canvas || typeof Chart === 'undefined') return;

        const data = @js($chartData);

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Monthly Progress',
                        data: data.monthly,
                        borderColor: '#e31c23',
                        backgroundColor: 'rgba(227, 28, 35, 0.10)',
                        pointBackgroundColor: '#e31c23',
                        pointBorderColor: '#ffffff',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        borderWidth: 2.5,
                        tension: 0.3,
                        fill: false,
                        spanGaps: false,
                    },
                    {
                        label: 'Expected Progress',
                        data: data.expected,
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.08)',
                        pointBackgroundColor: '#0ea5e9',
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        borderWidth: 2.5,
                        tension: 0.25,
                        fill: false,
                    },
                    {
                        label: 'Benchmark',
                        data: data.benchmark,
                        borderColor: '#64748b',
                        borderDash: [6, 4],
                        pointRadius: 0,
                        borderWidth: 2,
                        fill: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            font: { weight: '600' },
                        },
                    },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                const value = context.parsed.y;
                                if (value === null || value === undefined) {
                                    return context.dataset.label + ': No data';
                                }
                                return context.dataset.label + ': ' + value;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        title: {
                            display: true,
                            text: 'KPI month range',
                            font: { weight: '600' },
                        },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(148, 163, 184, 0.25)' },
                        title: {
                            display: true,
                            text: 'Progress',
                            font: { weight: '600' },
                        },
                    },
                },
            },
        });
    });
</script>
@endpush
