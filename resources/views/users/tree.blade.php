@extends('layouts.app')

@section('title', 'Users tree')
@section('page-title', 'Users tree')
@section('page-subtitle', 'Organization tree by designation order')

@section('content')
    <div
        x-data="userAssignmentsModal()"
        @open-user-assignments="open($event.detail)"
        @keydown.escape.window="visible && close()"
    >
        <form method="GET" class="mb-6 grid gap-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-4">
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-muted">Department</label>
                <select name="department_id" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    <option value="">All departments</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) ($filters['department_id'] ?? '') === (string) $department->id)>
                            {{ $department->displayName() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-muted">Designation</label>
                <select name="designation_id" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    <option value="">All designations</option>
                    @foreach($designations as $designation)
                        <option value="{{ $designation->id }}" @selected((string) ($filters['designation_id'] ?? '') === (string) $designation->id)>
                            {{ $designation->sort_order }}. {{ $designation->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2 md:col-span-2">
                <button class="rounded-2xl bg-ink px-5 py-2.5 text-sm font-bold text-white">Apply filters</button>
                <a href="{{ route('users.tree') }}" class="rounded-2xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600">Reset</a>
                <div class="ml-auto rounded-2xl bg-brand-50 px-4 py-2.5 text-sm font-bold text-brand-700">
                    {{ $totalUsers }} {{ $totalUsers === 1 ? 'person' : 'people' }}
                </div>
            </div>
        </form>

        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-gradient-to-r from-brand-50 via-white to-white px-5 py-4">
                <h2 class="text-lg font-extrabold tracking-tight">Organization tree</h2>
                <p class="text-sm text-muted">Sorted by designation order, then name. Click project or task counts to view details.</p>
            </div>

            <div class="px-4 py-8 sm:px-10">
                @if($roots->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-14 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm12 10v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                        </div>
                        <h3 class="mt-4 text-lg font-bold">No users to display</h3>
                        <p class="mt-1 text-sm text-muted">Try clearing filters to see more people.</p>
                    </div>
                @else
                    <div class="mx-auto max-w-3xl">
                        @foreach($roots as $index => $node)
                            @include('users.partials.tree-node', [
                                'node' => $node,
                                'isLast' => $index === $roots->count() - 1,
                                'depth' => 0,
                            ])
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @include('users.partials.assignments-modal')
    </div>
@endsection
