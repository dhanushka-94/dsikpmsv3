@extends('layouts.app')

@section('title', 'Users')
@section('page-title', 'Users')
@section('page-subtitle', 'Manage system users and access')

@section('actions')
    <a href="{{ route('users.create') }}" class="rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700">
        Add user
    </a>
@endsection

@section('content')
    <form method="GET" class="mb-5 grid gap-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, EPF..." class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100 md:col-span-2">
        <select name="role" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm">
            <option value="">All roles</option>
            @foreach(\App\Enums\UserRole::options() as $value => $label)
                <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm">
            <option value="">All statuses</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
        </select>
        <div class="md:col-span-4 flex gap-2">
            <button class="rounded-2xl bg-ink px-4 py-2.5 text-sm font-bold text-white">Filter</button>
            <a href="{{ route('users.index') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Reset</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-muted">
                    <tr>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">EPF</th>
                        <th class="px-4 py-3">Department</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if($user->profilePictureUrl())
                                        <img src="{{ $user->profilePictureUrl() }}" class="h-10 w-10 rounded-full object-cover" alt="">
                                    @else
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 font-bold text-brand-700">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                                    @endif
                                    <div>
                                        <a href="{{ route('users.show', $user) }}" class="font-semibold hover:text-brand-700">{{ $user->displayName() }}</a>
                                        <p class="text-xs text-muted">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ $user->epf_number }}</td>
                            <td class="px-4 py-3">{{ $user->department?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold">{{ $user->role->label() }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('users.show', $user) }}" class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold">View</a>
                                    <a href="{{ route('users.edit', $user) }}" class="rounded-xl border border-brand-200 bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-muted">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $users->links() }}</div>
        @endif
    </div>
@endsection
