@extends('layouts.app')

@section('title', 'Departments')
@section('page-title', 'Departments')
@section('page-subtitle', 'Organize users by department')

@section('actions')
    <a href="{{ route('departments.create') }}" class="rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-700">Add department</a>
@endsection

@section('content')
    <form method="GET" class="mb-5 flex flex-col gap-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search departments..." class="flex-1 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
        <button class="rounded-2xl bg-ink px-4 py-2.5 text-sm font-bold text-white">Search</button>
    </form>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-muted">
                <tr>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Parent</th>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Users</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($departments as $department)
                    <tr>
                        <td class="px-4 py-3">
                            <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-xl bg-slate-100 px-2 text-xs font-bold text-slate-700">{{ $department->sort_order }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold">{{ $department->name }}</p>
                            @if($department->children_count)
                                <p class="text-xs text-muted">{{ $department->children_count }} {{ $department->children_count === 1 ? 'child department' : 'child departments' }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $department->parent?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $department->code ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $department->users_count }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $department->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $department->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('departments.edit', $department) }}" class="rounded-xl border border-brand-200 bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700">Edit</a>
                                <form method="POST" action="{{ route('departments.destroy', $department) }}" onsubmit="return requestDeleteConfirm(event, { title: 'Delete this department?', message: 'This department will be permanently removed.' })">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-xl border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-muted">No departments found.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($departments->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $departments->links() }}</div>
        @endif
    </div>
@endsection
