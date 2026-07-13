@php
    $project = $project ?? null;
    $assigneeSeed = collect($selectedAssignees ?? [])->values()->all();
    $userOptions = $assignableUsers->map(fn ($u) => [
        'id' => $u->id,
        'name' => $u->displayName(),
        'meta' => trim(($u->designation?->name ?? '').($u->department ? ' · '.$u->department->name : '')),
    ])->values();
@endphp

<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-base font-bold">Project details</h2>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
            <label class="mb-1.5 block text-sm font-semibold">Project name <span class="text-brand-600">*</span></label>
            <input type="text" name="name" value="{{ old('name', $project?->name) }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-semibold">Project year <span class="text-brand-600">*</span></label>
            <select name="year" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                @foreach($years as $value => $label)
                    <option value="{{ $value }}" @selected((string) old('year', $project?->year ?? now('Asia/Colombo')->year) === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-semibold">Category <span class="text-brand-600">*</span></label>
            <select name="project_category_id" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                <option value="">Select category</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('project_category_id', $project?->project_category_id) === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-semibold">Department <span class="text-brand-600">*</span></label>
            <select name="department_id" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                <option value="">Select department</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) old('department_id', $project?->department_id) === (string) $department->id)>{{ $department->displayName() }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-semibold">Reference number</label>
            <input type="text" name="reference_number" value="{{ old('reference_number', $project?->reference_number) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-semibold">Status <span class="text-brand-600">*</span></label>
            <select name="status" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $project?->status?->value ?? 'ongoing') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-semibold">Start date</label>
            <input type="date" name="start_date" value="{{ old('start_date', optional($project?->start_date)->format('Y-m-d')) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-semibold">End date</label>
            <input type="date" name="end_date" value="{{ old('end_date', optional($project?->end_date)->format('Y-m-d')) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
        </div>

        <div class="md:col-span-2">
            <label class="mb-1.5 block text-sm font-semibold">Project description</label>
            <textarea name="description" rows="4" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">{{ old('description', $project?->description) }}</textarea>
        </div>
    </div>
</div>

<div
    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
    x-data="{
        users: @js($userOptions),
        assignees: @js($assigneeSeed),
        search: '',
        get available() {
            const selected = this.assignees.map(a => String(a.user_id));
            return this.users.filter(u => !selected.includes(String(u.id)) && (!this.search || u.name.toLowerCase().includes(this.search.toLowerCase()) || (u.meta || '').toLowerCase().includes(this.search.toLowerCase())));
        },
        add(userId) {
            if (!userId) return;
            this.assignees.push({ user_id: String(userId), permission: 'viewer', is_enabled: true });
            this.search = '';
        },
        remove(index) {
            this.assignees.splice(index, 1);
        },
        labelFor(userId) {
            const user = this.users.find(u => String(u.id) === String(userId));
            return user ? user.name : 'Unknown user';
        },
        metaFor(userId) {
            const user = this.users.find(u => String(u.id) === String(userId));
            return user ? (user.meta || '') : '';
        }
    }"
>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-base font-bold">Assign users</h2>
            <p class="mt-1 text-sm text-muted">Default permission is Viewer. Disable a user to revoke access without removing them.</p>
        </div>
        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700" x-text="assignees.length + ' assigned'"></span>
    </div>

    <div class="mt-4 grid gap-3 md:grid-cols-[1fr_auto]">
        <input type="text" x-model="search" placeholder="Search users to assign..." class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
        <select class="rounded-2xl border border-slate-200 px-4 py-3 text-sm" @change="add($event.target.value); $event.target.value = ''">
            <option value="">Add user...</option>
            <template x-for="user in available" :key="user.id">
                <option :value="user.id" x-text="user.name + (user.meta ? ' — ' + user.meta : '')"></option>
            </template>
        </select>
    </div>

    <div class="mt-4 space-y-3">
        <template x-for="(assignee, index) in assignees" :key="assignee.user_id + '-' + index">
            <div class="flex flex-col gap-3 rounded-2xl border p-4 sm:flex-row sm:items-center"
                 :class="assignee.is_enabled !== false ? 'border-slate-200 bg-slate-50/80' : 'border-slate-200 bg-slate-100 opacity-70'">
                <input type="hidden" :name="'assignees[' + index + '][user_id]'" :value="assignee.user_id">
                <input type="hidden" :name="'assignees[' + index + '][is_enabled]'" :value="assignee.is_enabled !== false ? 1 : 0">
                <div class="min-w-0 flex-1">
                    <p class="font-semibold" :class="assignee.is_enabled === false && 'line-through text-slate-500'" x-text="labelFor(assignee.user_id)"></p>
                    <p class="text-xs text-muted" x-text="metaFor(assignee.user_id)"></p>
                </div>
                <select :name="'assignees[' + index + '][permission]'" x-model="assignee.permission" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold">
                    @foreach($permissions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <button type="button"
                        class="rounded-xl px-3 py-2 text-xs font-bold"
                        :class="assignee.is_enabled !== false ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'"
                        @click="assignee.is_enabled = !(assignee.is_enabled !== false)"
                        x-text="assignee.is_enabled !== false ? 'Disable' : 'Enable'"></button>
                <button type="button" class="rounded-xl border border-red-200 px-3 py-2 text-xs font-bold text-red-700" @click="remove(index)">Remove</button>
            </div>
        </template>
        <p x-show="assignees.length === 0" class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-muted">No users assigned yet.</p>
    </div>
</div>
