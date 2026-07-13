@extends('layouts.app')

@section('title', 'Plants')
@section('page-title', 'Plants')
@section('page-subtitle', 'Manage plants under each company')

@section('actions')
    <a href="{{ route('companies.index') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Companies</a>
    <a href="{{ route('plants.create', request()->only('company_id')) }}" class="rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-700">Add plant</a>
@endsection

@section('content')
    <form method="GET" class="mb-5 grid gap-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search plants..." class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100 md:col-span-2">
        <select name="company_id" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm">
            <option value="">All companies</option>
            @foreach($companies as $company)
                <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->name }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button class="rounded-2xl bg-ink px-4 py-2.5 text-sm font-bold text-white">Filter</button>
            <a href="{{ route('plants.index') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Reset</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-muted">
                <tr>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Plant</th>
                    <th class="px-4 py-3">Company</th>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Users</th>
                    <th class="px-4 py-3">Projects</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($plants as $plant)
                    <tr>
                        <td class="px-4 py-3">
                            <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-xl bg-slate-100 px-2 text-xs font-bold text-slate-700">{{ $plant->sort_order }}</span>
                        </td>
                        <td class="px-4 py-3 font-semibold">{{ $plant->name }}</td>
                        <td class="px-4 py-3">{{ $plant->company?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $plant->code ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $plant->users_count }}</td>
                        <td class="px-4 py-3">{{ $plant->projects_count }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $plant->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $plant->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('plants.edit', $plant) }}" class="rounded-xl border border-brand-200 bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700">Edit</a>
                                <form method="POST" action="{{ route('plants.destroy', $plant) }}" onsubmit="return requestDeleteConfirm(event, { title: 'Delete this plant?', message: 'This plant will be permanently removed.' })">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-xl border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-muted">No plants found.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($plants->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $plants->links() }}</div>
        @endif
    </div>
@endsection
