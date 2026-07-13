@extends('layouts.app')

@section('title', $user->displayName())
@section('page-title', $user->displayName())
@section('page-subtitle', $user->email)

@section('actions')
    <a href="{{ route('users.edit', $user) }}" class="rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-700">Edit</a>
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
                @if($user->profilePictureUrl())
                    <img src="{{ $user->profilePictureUrl() }}" class="h-28 w-28 rounded-3xl object-cover ring-4 ring-brand-50" alt="">
                @else
                    <div class="flex h-28 w-28 items-center justify-center rounded-3xl bg-brand-50 text-4xl font-extrabold text-brand-600">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
                <dl class="grid flex-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Title</dt>
                        <dd class="mt-1 font-semibold">{{ $user->title?->value ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">EPF Number</dt>
                        <dd class="mt-1 font-semibold">{{ $user->epf_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Department</dt>
                        <dd class="mt-1 font-semibold">{{ $user->department?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Designation</dt>
                        <dd class="mt-1 font-semibold">{{ $user->designation?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Role</dt>
                        <dd class="mt-1 font-semibold">{{ $user->role->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Parent user</dt>
                        <dd class="mt-1 font-semibold">{{ $user->parent?->displayName() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Status</dt>
                        <dd class="mt-1">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">Must change password</dt>
                        <dd class="mt-1 font-semibold">{{ $user->must_change_password ? 'Yes' : 'No' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="space-y-4">
            @if(auth()->user()->canResetPasswords())
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-bold">Reset password</h3>
                    <p class="mt-1 text-sm text-muted">Generate a temporary password. Optionally email it, or copy the details to share manually.</p>
                    <form method="POST" action="{{ route('users.reset-password', $user) }}" class="mt-4 space-y-3">
                        @csrf
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="send_email" value="1" class="rounded text-brand-600 focus:ring-brand-500">
                            Send new password by email
                        </label>
                        <button class="w-full rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-700">
                            Reset & show credentials
                        </button>
                    </form>
                </div>
            @endif

            <a href="{{ route('activity-logs.user', $user) }}" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-center text-sm font-bold text-slate-700 shadow-sm hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700">
                View user activity
            </a>

            <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Delete this user permanently?')">
                @csrf
                @method('DELETE')
                <button class="w-full rounded-2xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-bold text-red-700 hover:bg-red-100">
                    Delete user
                </button>
            </form>
        </div>
    </div>
@endsection
