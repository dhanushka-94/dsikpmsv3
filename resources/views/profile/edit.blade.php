@extends('layouts.app')

@section('title', 'My profile')
@section('page-title', 'My profile')
@section('page-subtitle', 'Update your personal details')

@section('content')
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mx-auto max-w-4xl space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-bold">Basic information</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">Title <span class="text-brand-600">*</span></label>
                    <select name="title" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                        <option value="">Select title</option>
                        @foreach($titles as $value => $label)
                            <option value="{{ $value }}" @selected(old('title', $user->title?->value) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold">Name <span class="text-brand-600">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold">EPF Number</label>
                    <input type="text" name="epf_number" value="{{ old('epf_number', $user->epf_number) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold">Email (username)</label>
                    <input type="email" value="{{ $user->email }}" disabled class="w-full cursor-not-allowed rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm text-slate-500">
                    <p class="mt-1 text-xs text-muted">Email cannot be changed. Contact an administrator if needed.</p>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-bold">Organization</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <x-searchable-select
                    name="designation_id"
                    :options="$designations"
                    :selected="old('designation_id', $user->designation_id)"
                    label="Designation"
                    placeholder="Search designation..."
                    required
                />

                <div>
                    <label class="mb-1.5 block text-sm font-semibold">Department <span class="text-brand-600">*</span></label>
                    <select name="department_id" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                        <option value="">Select department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) old('department_id', $user->department_id) === (string) $department->id)>{{ $department->displayName() }}</option>
                        @endforeach
                    </select>
                </div>

                <x-searchable-select
                    name="parent_user_id"
                    :options="$parentUsers"
                    :selected="old('parent_user_id', $user->parent_user_id)"
                    label="Parent user"
                    placeholder="Search parent user..."
                    option-label="name"
                />

                <div>
                    <label class="mb-1.5 block text-sm font-semibold">Role</label>
                    <input type="text" value="{{ $user->role->label() }}" disabled class="w-full cursor-not-allowed rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm text-slate-500">
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-bold">Profile picture</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <input type="file" name="profile_picture" accept="image/*" class="w-full rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm">
                    @if($user->profilePictureUrl())
                        <div class="mt-3 flex items-center gap-3">
                            <img src="{{ $user->profilePictureUrl() }}" class="h-14 w-14 rounded-2xl object-cover" alt="">
                            <label class="flex items-center gap-2 text-sm text-muted">
                                <input type="checkbox" name="remove_profile_picture" value="1">
                                Remove current picture
                            </label>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-bold">Change password</h2>
            <p class="mt-1 text-sm text-muted">Leave blank to keep your current password.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold">Current password</label>
                    <input type="password" name="current_password" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">New password</label>
                    <input type="password" name="password" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">Confirm new password</label>
                    <input type="password" name="password_confirmation" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white hover:bg-brand-700">Save profile</button>
            <a href="{{ route('dashboard') }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600">Cancel</a>
        </div>
    </form>
@endsection
