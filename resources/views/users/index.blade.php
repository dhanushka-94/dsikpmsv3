@extends('layouts.app')

@section('title', 'Users')
@section('page-title', 'Users')
@section('page-subtitle', 'Manage system users and access')

@section('actions')
    <div class="flex flex-wrap items-center gap-2">
        @if(auth()->user()->isSuperAdmin())
            <form
                method="POST"
                action="{{ route('users.reset-all-passwords') }}"
                onsubmit="return confirm('Reset auto-generated passwords for ALL other users? A CSV download with the new passwords will start. Your own password will not change.')"
            >
                @csrf
                <input type="hidden" name="confirm" value="1">
                <button type="submit" class="rounded-2xl border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-bold text-amber-800 hover:bg-amber-100">
                    Reset all passwords
                </button>
            </form>
        @endif
        <a href="{{ route('users.create') }}" class="rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700">
            Add user
        </a>
    </div>
@endsection

@section('content')
    <div
        x-data="{
            ...userAssignmentsModal(),
            view: localStorage.getItem('users_index_view') || 'cards',
            setView(mode) {
                this.view = mode;
                localStorage.setItem('users_index_view', mode);
            }
        }"
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
            <div class="md:col-span-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex gap-2">
                    <button class="rounded-2xl bg-ink px-4 py-2.5 text-sm font-bold text-white">Filter</button>
                    <a href="{{ route('users.index') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Reset</a>
                </div>

                <div class="inline-flex rounded-2xl border border-slate-200 bg-slate-50 p-1">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-bold transition"
                        :class="view === 'cards' ? 'bg-white text-brand-700 shadow-sm' : 'text-slate-500 hover:text-ink'"
                        @click="setView('cards')"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5h6v6H4V5zm10 0h6v6h-6V5zM4 13h6v6H4v-6zm10 0h6v6h-6v-6z"/></svg>
                        Cards
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-bold transition"
                        :class="view === 'list' ? 'bg-white text-brand-700 shadow-sm' : 'text-slate-500 hover:text-ink'"
                        @click="setView('list')"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        List
                    </button>
                </div>
            </div>
        </form>

        @if($users->isEmpty())
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm">
                <p class="font-semibold">No users found.</p>
            </div>
        @else
            {{-- Card view --}}
            <div x-show="view === 'cards'" x-cloak class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($users as $user)
                    <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
                        <a href="{{ route('users.profile', $user) }}" class="block">
                            <div class="flex items-start gap-3">
                                @if($user->profilePictureUrl())
                                    <x-profile-photo :url="$user->profilePictureUrl()" class="h-12 w-12" rounded="rounded-2xl" ring="ring-2 ring-brand-50" :alt="$user->displayName()" />
                                @else
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-base font-extrabold text-brand-700">
                                        {{ strtoupper(substr($user->calling_name ?: $user->name, 0, 1)) }}
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
                @endforeach
            </div>

            {{-- List view --}}
            <div x-show="view === 'list'" x-cloak class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-muted">
                            <tr>
                                <th class="px-4 py-3">User</th>
                                <th class="px-4 py-3">Calling name</th>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">EPF</th>
                                <th class="px-4 py-3">Company / Plant</th>
                                <th class="px-4 py-3">Department</th>
                                <th class="px-4 py-3">Designation</th>
                                <th class="px-4 py-3">Role</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($users as $user)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if($user->profilePictureUrl())
                                                <x-profile-photo :url="$user->profilePictureUrl()" class="h-10 w-10" rounded="rounded-xl" ring="ring-2 ring-brand-50" :alt="$user->displayName()" />
                                            @else
                                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-sm font-extrabold text-brand-700">
                                                    {{ strtoupper(substr($user->calling_name ?: $user->name, 0, 1)) }}
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <a href="{{ route('users.profile', $user) }}" class="block truncate font-bold hover:text-brand-700">{{ $user->calling_name ?: $user->name }}</a>
                                                <p class="truncate text-xs text-muted">{{ $user->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700">{{ $user->calling_name ?: '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $user->name ?: '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $user->epf_number ?: '—' }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-slate-700">{{ $user->company?->name ?? '—' }}</p>
                                        <p class="text-xs text-muted">{{ $user->plant?->name ?? '—' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $user->department?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $user->designation?->name ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">{{ $user->role->label() }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <div class="inline-flex gap-2">
                                            <a href="{{ route('users.profile', $user) }}" class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold">Profile</a>
                                            <a href="{{ route('users.edit', $user) }}" class="rounded-xl border border-brand-200 bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700">Edit</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if($users->hasPages())
            <div class="mt-5">{{ $users->links() }}</div>
        @endif

        @include('users.partials.assignments-modal')
    </div>
@endsection
