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
    <div
        x-data="userAssignmentsModal()"
        @open-user-assignments="open($event.detail)"
        @keydown.escape.window="visible && close()"
    >
        <form method="GET" class="mb-5 grid gap-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, EPF..." class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100 md:col-span-2">
            <select name="company_id" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm">
                <option value="">All companies</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
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

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @forelse($users as $user)
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
                    <a href="{{ route('users.profile', $user) }}" class="block">
                        <div class="flex items-start gap-3">
                            @if($user->profilePictureUrl())
                                <img src="{{ $user->profilePictureUrl() }}" class="h-12 w-12 rounded-2xl object-cover ring-2 ring-brand-50" alt="">
                            @else
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-base font-extrabold text-brand-700">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-bold hover:text-brand-700">{{ $user->displayName() }}</p>
                                <p class="truncate text-xs text-muted">{{ $user->email }}</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">{{ $user->role->label() }}</span>
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                    <div class="mt-4 space-y-1 text-xs text-muted">
                        <p><span class="font-semibold text-slate-600">Company:</span> {{ $user->company?->name ?? '—' }}</p>
                        <p><span class="font-semibold text-slate-600">Plant:</span> {{ $user->plant?->name ?? '—' }}</p>
                        <p><span class="font-semibold text-slate-600">EPF:</span> {{ $user->epf_number ?: '—' }}</p>
                        <p><span class="font-semibold text-slate-600">Department:</span> {{ $user->department?->name ?? '—' }}</p>
                        <p><span class="font-semibold text-slate-600">Designation:</span> {{ $user->designation?->name ?? '—' }}</p>
                    </div>
                    </a>

                    @unless($user->isSuperAdmin())
                        <div class="mt-4 flex flex-wrap gap-2">
                            @include('users.partials.assignment-count-badges', ['user' => $user])
                        </div>
                    @endunless

                    <div class="mt-4 flex gap-2 border-t border-slate-100 pt-4">
                        <a href="{{ route('users.profile', $user) }}" class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold">Open profile</a>
                        <a href="{{ route('users.edit', $user) }}" class="rounded-xl border border-brand-200 bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700">Edit</a>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm sm:col-span-2 xl:col-span-3">
                    <p class="font-semibold">No users found.</p>
                </div>
            @endforelse
        </div>

        @if($users->hasPages())
            <div class="mt-5">{{ $users->links() }}</div>
        @endif

        @include('users.partials.assignments-modal')
    </div>
@endsection
