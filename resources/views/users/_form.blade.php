@php $user = $user ?? null; @endphp

<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-base font-bold">Basic information</h2>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-sm font-semibold">Title <span class="text-brand-600">*</span></label>
            <select name="title" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                <option value="">Select title</option>
                @foreach($titles as $value => $label)
                    <option value="{{ $value }}" @selected(old('title', $user?->title?->value) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-semibold">Name <span class="text-brand-600">*</span></label>
            <input type="text" name="name" value="{{ old('name', $user?->name) }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-semibold">EPF Number</label>
            <input type="text" name="epf_number" value="{{ old('epf_number', $user?->epf_number) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-semibold">Email (username) <span class="text-brand-600">*</span></label>
            <input type="email" name="email" value="{{ old('email', $user?->email) }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
        </div>
    </div>
</div>

<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-base font-bold">Organization</h2>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        @include('partials.company-plant-fields', [
            'companies' => $companies,
            'plants' => $plants,
            'selectedCompanyId' => old('company_id', $user?->company_id),
            'selectedPlantId' => old('plant_id', $user?->plant_id),
        ])

        <x-searchable-select
            name="designation_id"
            :options="$designations"
            :selected="old('designation_id', $user?->designation_id)"
            label="Designation"
            placeholder="Search designation..."
            required
        />

        <div>
            <label class="mb-1.5 block text-sm font-semibold">Department <span class="text-brand-600">*</span></label>
            <select name="department_id" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                <option value="">Select department</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) old('department_id', $user?->department_id) === (string) $department->id)>{{ $department->displayName() }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-semibold">User role <span class="text-brand-600">*</span></label>
            <select name="role" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                <option value="">Select role</option>
                @foreach($roles as $value => $label)
                    <option value="{{ $value }}" @selected(old('role', $user?->role?->value) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <x-searchable-select
            name="parent_user_id"
            :options="$parentUsers"
            :selected="old('parent_user_id', $user?->parent_user_id)"
            label="Parent user"
            placeholder="Search parent user..."
            option-label="name"
        />
    </div>
</div>

<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-base font-bold">Profile & status</h2>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-sm font-semibold">Profile picture</label>
            <input type="file" name="profile_picture" accept="image/*" class="w-full rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm">
            @if($user?->profilePictureUrl())
                <div class="mt-3 flex items-center gap-3">
                    <img src="{{ $user->profilePictureUrl() }}" class="h-14 w-14 rounded-2xl object-cover" alt="">
                    <label class="flex items-center gap-2 text-sm text-muted">
                        <input type="checkbox" name="remove_profile_picture" value="1">
                        Remove current picture
                    </label>
                </div>
            @endif
        </div>

        <div class="space-y-4">
            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3">
                <input type="checkbox" name="is_active" value="1" class="rounded text-brand-600 focus:ring-brand-500" @checked(old('is_active', $user?->is_active ?? true))>
                <span>
                    <span class="block text-sm font-semibold">Active</span>
                    <span class="text-xs text-muted">Inactive users cannot sign in</span>
                </span>
            </label>

            @unless($user)
                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3">
                    <input type="checkbox" name="send_credentials_email" value="1" class="rounded text-brand-600 focus:ring-brand-500" @checked(old('send_credentials_email'))>
                    <span>
                        <span class="block text-sm font-semibold">Email temporary password</span>
                        <span class="text-xs text-muted">Also show copyable credentials after create</span>
                    </span>
                </label>
            @endunless
        </div>
    </div>
</div>
