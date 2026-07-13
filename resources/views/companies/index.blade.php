@extends('layouts.app')

@section('title', 'Companies')
@section('page-title', 'Companies')
@section('page-subtitle', 'Manage companies')

@section('actions')
    <a href="{{ route('plants.index') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Plants</a>
    <a href="{{ route('companies.create') }}" class="rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-700">Add company</a>
@endsection

@section('content')
    <form method="GET" class="mb-5 flex flex-col gap-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search companies..." class="flex-1 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
        <button class="rounded-2xl bg-ink px-4 py-2.5 text-sm font-bold text-white">Search</button>
    </form>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-muted">
                <tr>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Plants</th>
                    <th class="px-4 py-3">Users</th>
                    <th class="px-4 py-3">Projects</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($companies as $company)
                    <tr>
                        <td class="px-4 py-3">
                            <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-xl bg-slate-100 px-2 text-xs font-bold text-slate-700">{{ $company->sort_order }}</span>
                        </td>
                        <td class="px-4 py-3 font-semibold">{{ $company->name }}</td>
                        <td class="px-4 py-3">{{ $company->code ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('plants.index', ['company_id' => $company->id]) }}" class="font-semibold text-brand-700 hover:underline">{{ $company->plants_count }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $company->users_count }}</td>
                        <td class="px-4 py-3">{{ $company->projects_count }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $company->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $company->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('plants.create', ['company_id' => $company->id]) }}" class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold">Add plant</a>
                                <a href="{{ route('companies.edit', $company) }}" class="rounded-xl border border-brand-200 bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700">Edit</a>
                                <form method="POST" action="{{ route('companies.destroy', $company) }}" onsubmit="return requestDeleteConfirm(event, { title: 'Delete this company?', message: 'This company will be permanently removed.' })">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-xl border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-muted">No companies found.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($companies->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $companies->links() }}</div>
        @endif
    </div>
@endsection
