@php
    $project = $project ?? null;
    $assigneeSeed = collect($selectedAssignees ?? [])->values()->all();
    $userOptions = $assignableUsers->map(fn ($u) => [
        'id' => $u->id,
        'name' => $u->displayName(),
        'calling_name' => $u->calling_name,
        'meta' => trim(($u->designation?->name ?? '').($u->department ? ' · '.$u->department->name : '')),
        'initial' => strtoupper(substr($u->calling_name ?: $u->name, 0, 1)),
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
                    <option value="{{ $value }}" @selected((string) old('year', $project?->year ?? now()->year) === (string) $value)>{{ $label }}</option>
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

        @include('partials.company-plant-fields', [
            'companies' => $companies,
            'plants' => $plants,
            'selectedCompanyId' => old('company_id', $project?->company_id),
            'selectedPlantId' => old('plant_id', $project?->plant_id),
        ])

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
        open: false,
        get available() {
            const selected = this.assignees.map(a => String(a.user_id));
            const needle = this.search.trim().toLowerCase();
            return this.users.filter((u) => {
                if (selected.includes(String(u.id))) return false;
                if (!needle) return true;
                return [u.name, u.calling_name, u.meta].filter(Boolean).some((value) => String(value).toLowerCase().includes(needle));
            });
        },
        userFor(userId) {
            return this.users.find(u => String(u.id) === String(userId));
        },
        add(userId) {
            if (!userId) return;
            this.assignees.push({ user_id: String(userId), permission: 'viewer', is_enabled: true });
            this.search = '';
            this.open = false;
        },
        remove(index) {
            this.assignees.splice(index, 1);
        },
        requestRemoveAssignee(index) {
            const assignee = this.assignees[index];
            const name = this.labelFor(assignee ? assignee.user_id : null);
            requestRemoveConfirm({
                title: 'Remove this user?',
                message: 'Remove ' + name + ' from the project assignment list.',
                onConfirm: () => this.remove(index),
            });
        },
        labelFor(userId) {
            return this.userFor(userId)?.name || 'Unknown user';
        },
        metaFor(userId) {
            return this.userFor(userId)?.meta || '';
        },
        initialFor(userId) {
            return this.userFor(userId)?.initial || '?';
        }
    }"
    @click.outside="open = false"
>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-base font-bold">Assign users</h2>
            <p class="mt-1 text-sm text-muted">Search by name, calling name, designation, or department. Default permission is Viewer.</p>
        </div>
        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700" x-text="assignees.length + ' assigned'"></span>
    </div>

    <div class="relative mt-4">
        <label class="mb-1.5 block text-sm font-semibold">Add team member</label>
        <button
            type="button"
            @click="open = !open; $nextTick(() => { if (open) $refs.userSearch?.focus() })"
            class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"
        >
            <span class="text-muted">Search and add a user...</span>
            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition
            class="absolute z-30 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
        >
            <div class="border-b border-slate-100 p-2">
                <input
                    x-ref="userSearch"
                    type="text"
                    x-model="search"
                    placeholder="Search users..."
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500"
                    @click.stop
                    @keydown.escape.prevent="open = false"
                >
            </div>
            <ul class="max-h-64 overflow-y-auto py-1">
                <template x-for="user in available" :key="user.id">
                    <li>
                        <button
                            type="button"
                            class="flex w-full items-center gap-3 px-3 py-2.5 text-left hover:bg-brand-50"
                            @click="add(user.id)"
                        >
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-xs font-extrabold text-brand-700" x-text="user.initial"></span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-ink" x-text="user.name"></span>
                                <span class="block truncate text-xs text-muted" x-text="user.meta || 'No designation'"></span>
                            </span>
                        </button>
                    </li>
                </template>
                <li x-show="available.length === 0" class="px-4 py-6 text-center text-sm text-muted">
                    <span x-show="search.trim()">No matching users</span>
                    <span x-show="!search.trim()">All available users are already assigned</span>
                </li>
            </ul>
        </div>
    </div>

    <div class="mt-4 space-y-3">
        <template x-for="(assignee, index) in assignees" :key="assignee.user_id + '-' + index">
            <div class="flex flex-col gap-3 rounded-2xl border p-4 sm:flex-row sm:items-center"
                 :class="assignee.is_enabled !== false ? 'border-slate-200 bg-slate-50/80' : 'border-dashed border-slate-300 bg-slate-100/80'">
                <input type="hidden" :name="'assignees[' + index + '][user_id]'" :value="assignee.user_id">
                <input type="hidden" :name="'assignees[' + index + '][is_enabled]'" :value="assignee.is_enabled !== false ? 1 : 0">
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-brand-50 text-sm font-extrabold text-brand-700" x-text="initialFor(assignee.user_id)"></div>
                    <div class="min-w-0">
                        <p class="truncate font-semibold" :class="assignee.is_enabled === false && 'line-through text-slate-500'" x-text="labelFor(assignee.user_id)"></p>
                        <p class="truncate text-xs text-muted" x-text="metaFor(assignee.user_id) || '—'"></p>
                    </div>
                    <span
                        class="hidden rounded-full px-2.5 py-1 text-[11px] font-bold sm:inline-flex"
                        :class="assignee.is_enabled !== false ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-200 text-slate-500'"
                        x-text="assignee.is_enabled !== false ? 'Active' : 'Disabled'"
                    ></span>
                </div>
                <div class="flex flex-wrap items-center gap-2 sm:shrink-0">
                    <select :name="'assignees[' + index + '][permission]'" x-model="assignee.permission" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                        @foreach($permissions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="button"
                            class="rounded-xl px-3 py-2 text-xs font-bold"
                            :class="assignee.is_enabled !== false ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'"
                            @click="assignee.is_enabled = !(assignee.is_enabled !== false)"
                            x-text="assignee.is_enabled !== false ? 'Disable' : 'Enable'"></button>
                    <button type="button" class="rounded-xl border border-red-200 px-3 py-2 text-xs font-bold text-red-700" @click="requestRemoveAssignee(index)">Remove</button>
                </div>
            </div>
        </template>
        <p x-show="assignees.length === 0" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/50 px-4 py-8 text-center text-sm text-muted">No users assigned yet. Use search above to add the team.</p>
    </div>
</div>
