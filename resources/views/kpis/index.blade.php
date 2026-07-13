@extends('layouts.app')

@section('title', 'KPIs')
@section('page-title', 'KPIs')
@section('page-subtitle', ($canManage ?? false) ? 'Manage key performance indicators' : 'Your assigned key performance indicators')

@section('actions')
    @if($canManage ?? false)
        <a href="{{ route('kpi-categories.index') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Categories</a>
        <a href="{{ route('kpis.create') }}" class="rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-700">Add KPI</a>
    @endif
@endsection

@section('content')
    <form method="GET" class="mb-6 grid gap-3 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-5">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search index, name, formula..." class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100 lg:col-span-2">
        <select name="kpi_category_id" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
            <option value="">All categories</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((string) request('kpi_category_id') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <select name="benchmark_type" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
            <option value="">All benchmark types</option>
            @foreach($benchmarkTypes as $value => $label)
                <option value="{{ $value }}" @selected(request('benchmark_type') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button class="rounded-2xl bg-ink px-4 py-3 text-sm font-bold text-white">Filter</button>
            <a href="{{ route('kpis.index') }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600">Reset</a>
        </div>
    </form>

    <div class="space-y-6">
        @forelse($kpis as $kpi)
            @php
                $fieldNames = $kpi->formulaVariables();
                $initialValues = collect($fieldNames)->mapWithKeys(fn ($name) => [$name => ''])->all();
                $openFeed = (string) old('kpi_id') === (string) $kpi->id;
            @endphp
            <article
                class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm"
                x-data="{
                    feedOpen: {{ $openFeed ? 'true' : 'false' }},
                    ...kpiQuickFeed(@js($kpi->formula), @js($initialValues))
                }"
            >
                <div class="border-b border-slate-100 bg-slate-50/70 px-6 py-5">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-xl bg-ink px-3 py-1.5 text-xs font-bold tracking-wide text-white">{{ $kpi->kpi_index ?: 'No index' }}</span>
                                <span class="rounded-full bg-white px-3 py-1.5 text-xs font-bold text-slate-600 ring-1 ring-slate-200">{{ $kpi->category?->name ?? 'Uncategorized' }}</span>
                                <span class="rounded-full px-3 py-1.5 text-xs font-bold {{ $kpi->benchmark_type->badgeClasses() }}">{{ $kpi->benchmark_type->label() }}</span>
                                <span class="rounded-full px-3 py-1.5 text-xs font-bold {{ $kpi->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $kpi->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <x-long-text
                                :text="$kpi->name"
                                :href="route('kpis.show', $kpi)"
                                class="mt-3 block text-2xl font-extrabold tracking-tight text-ink hover:text-brand-700"
                            />
                            @if($kpi->definition)
                                <x-long-text :text="$kpi->definition" :lines="2" class="mt-2 max-w-3xl text-sm leading-relaxed text-muted" />
                            @endif
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            @if(count($fieldNames))
                                <button
                                    type="button"
                                    class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-bold text-emerald-700 hover:bg-emerald-100"
                                    @click="feedOpen = !feedOpen"
                                    x-text="feedOpen ? 'Close feed' : 'Quick feed'"
                                ></button>
                            @endif
                            <a href="{{ route('kpis.show', $kpi) }}" class="rounded-2xl bg-ink px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800">View details</a>
                            @if($canManage ?? false)
                                <a href="{{ route('kpis.edit', $kpi) }}" class="rounded-2xl border border-brand-200 bg-brand-50 px-4 py-2.5 text-sm font-bold text-brand-700 hover:bg-brand-100">Edit</a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="px-6 py-5">
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-2xl border border-brand-100 bg-brand-50/60 px-4 py-4">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-brand-700/70">Last result</p>
                            <p class="mt-2 text-3xl font-extrabold text-brand-700">{{ $kpi->formula_result ?? '—' }}</p>
                            <p class="mt-1 text-xs font-semibold text-muted">{{ $kpi->results_count }} history {{ $kpi->results_count === 1 ? 'entry' : 'entries' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-4">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Benchmark</p>
                            <p class="mt-2 text-3xl font-extrabold text-ink">{{ $kpi->benchmark_percent }}%</p>
                            <p class="mt-1 text-xs font-semibold text-muted">{{ $kpi->benchmark_type->label() }} target</p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-4">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Period</p>
                            <p class="mt-2 text-base font-extrabold text-ink">{{ $kpi->start_date->format('Y-m-d') }}</p>
                            <p class="mt-1 text-sm font-semibold text-muted">to {{ $kpi->end_date->format('Y-m-d') }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-4">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Coverage</p>
                            <p class="mt-2 text-3xl font-extrabold text-ink">{{ $kpi->projects_count }} <span class="text-lg font-bold text-muted">/</span> {{ $kpi->users_count }}</p>
                            <p class="mt-1 text-xs font-semibold text-muted">Projects / weightage users</p>
                        </div>
                    </div>
                </div>

                <div
                    x-show="feedOpen"
                    x-cloak
                    class="border-t border-slate-100 bg-white px-6 py-6"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-bold">Quick formula data feed</h3>
                            <p class="mt-1 text-sm text-muted">Enter date + values and save to history without opening the KPI page.</p>
                        </div>
                        <div class="rounded-2xl bg-brand-50 px-4 py-3 text-right">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-brand-700/70">Live result</p>
                            <p class="text-2xl font-extrabold text-brand-700" x-text="resultLabel"></p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('kpis.calculate', $kpi) }}" class="mt-5 space-y-5">
                        @csrf
                        <input type="hidden" name="redirect_to" value="index">
                        <input type="hidden" name="kpi_id" value="{{ $kpi->id }}">

                        <div>
                            <label class="mb-1.5 block text-sm font-semibold">Date <span class="text-brand-600">*</span></label>
                            <input
                                type="date"
                                name="recorded_on"
                                value="{{ old('kpi_id') == $kpi->id ? old('recorded_on', now()->toDateString()) : now()->toDateString() }}"
                                required
                                min="{{ optional($kpi->start_date)->format('Y-m-d') }}"
                                max="{{ optional($kpi->end_date)->format('Y-m-d') }}"
                                class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100 sm:max-w-xs"
                            >
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($fieldNames as $variable)
                                <label class="block rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                    <span class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-muted">Value name</span>
                                    <span class="mb-3 block break-words text-sm font-bold [overflow-wrap:anywhere]" title="{{ $variable }}">{{ $variable }}</span>
                                    <span class="mb-1.5 block text-xs font-semibold text-slate-600">Value</span>
                                    <input
                                        type="number"
                                        step="any"
                                        required
                                        name="values[{{ $variable }}]"
                                        value="{{ old('kpi_id') == $kpi->id ? old('values.'.$variable, '') : '' }}"
                                        x-model="values[{{ json_encode($variable) }}]"
                                        @input="error = ''"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100"
                                    >
                                </label>
                            @endforeach
                        </div>

                        <p class="text-xs font-semibold text-red-600" x-show="error" x-text="error"></p>

                        <div class="flex flex-wrap gap-2">
                            <button type="submit" class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white hover:bg-brand-700">Save to history</button>
                            <button type="button" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600" @click="clearValues()">Clear</button>
                            <a href="{{ route('kpis.show', $kpi) }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600">Full history</a>
                        </div>
                    </form>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
                <p class="text-lg font-bold">No KPIs found.</p>
                <p class="mt-2 text-sm text-muted">Try adjusting filters{{ ($canManage ?? false) ? ', or create a new KPI.' : '.' }}</p>
            </div>
        @endforelse
    </div>

    @if($kpis->hasPages())
        <div class="mt-6">{{ $kpis->links() }}</div>
    @endif
@endsection

@push('scripts')
<script>
    function kpiQuickFeed(formula, initialValues) {
        return {
            formula: formula || '',
            values: { ...(initialValues || {}) },
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
</script>
@endpush
