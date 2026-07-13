@php
    $task = $task ?? null;
    $projectUsersMap = $projects->mapWithKeys(function ($project) use ($task) {
        $users = $project->users->filter(fn ($u) => (bool) ($u->pivot->is_enabled ?? true));

        if ($task && (int) $task->project_id === (int) $project->id) {
            $users = $users->merge($task->assignees)->unique('id');
        }

        return [
            $project->id => $users->sortBy('name')->values()->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->displayName(),
            ])->values(),
        ];
    });
@endphp

<div
    class="space-y-6"
    x-data="{
        projectId: @js((string) old('project_id', $selectedProject?->id ?? '')),
        projectUsers: @js($projectUsersMap),
        selected: @js(collect($selectedAssignees ?? [])->map(fn ($id) => (string) $id)->values()),
        startsAt: @js(old('starts_at', optional($task?->starts_at)->format('Y-m-d\TH:i') ?? now('Asia/Colombo')->format('Y-m-d\TH:i'))),
        endsAt: @js(old('ends_at', optional($task?->ends_at)->format('Y-m-d\TH:i') ?? now('Asia/Colombo')->addDay()->format('Y-m-d\TH:i'))),
        get assignees() {
            return this.projectUsers[this.projectId] || [];
        },
        toggle(id) {
            id = String(id);
            if (this.selected.includes(id)) {
                this.selected = this.selected.filter(v => v !== id);
            } else {
                this.selected.push(id);
            }
        },
        applyPreset(hours) {
            if (!this.startsAt) return;
            const start = new Date(this.startsAt);
            const end = new Date(start.getTime() + hours * 60 * 60 * 1000);
            this.endsAt = this.toLocalInput(end);
        },
        toLocalInput(date) {
            const pad = (n) => String(n).padStart(2, '0');
            return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()) + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
        },
        onProjectChange() {
            const allowed = (this.assignees || []).map(u => String(u.id));
            this.selected = this.selected.filter(id => allowed.includes(id));
        }
    }"
>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-base font-bold">Task details</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-semibold">Project <span class="text-brand-600">*</span></label>
                <select name="project_id" x-model="projectId" @change="onProjectChange()" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    <option value="">Select project</option>
                    @foreach($projects as $projectOption)
                        <option value="{{ $projectOption->id }}">{{ $projectOption->name }} ({{ $projectOption->year }})</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-semibold">Title <span class="text-brand-600">*</span></label>
                <input type="text" name="title" value="{{ old('title', $task?->title) }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-semibold">Priority <span class="text-brand-600">*</span></label>
                <select name="priority" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    @foreach($priorities as $value => $label)
                        <option value="{{ $value }}" @selected(old('priority', $task?->priority?->value ?? 'medium') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            @if($task)
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">Status <span class="text-brand-600">*</span></label>
                    <select name="status" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $task->status->value) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-semibold">Description</label>
                <textarea name="description" rows="4" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">{{ old('description', $task?->description) }}</textarea>
            </div>
        </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-bold">Duration</h2>
                <p class="mt-1 text-sm text-muted">Pick start time, then use a quick duration or set the end manually.</p>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700" @click="applyPreset(1)">1 hour</button>
            <button type="button" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700" @click="applyPreset(4)">Half day</button>
            <button type="button" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700" @click="applyPreset(24)">1 day</button>
            <button type="button" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700" @click="applyPreset(72)">3 days</button>
            <button type="button" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700" @click="applyPreset(168)">1 week</button>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-semibold">Start <span class="text-brand-600">*</span></label>
                <input type="datetime-local" name="starts_at" x-model="startsAt" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold">End <span class="text-brand-600">*</span></label>
                <input type="datetime-local" name="ends_at" x-model="endsAt" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            </div>
        </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-bold">Assign to</h2>
                <p class="mt-1 text-sm text-muted">Choose from users assigned to the selected project.</p>
            </div>
            <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700" x-text="selected.length + ' selected'"></span>
        </div>

        <div class="mt-4 grid gap-2 sm:grid-cols-2" x-show="projectId && assignees.length">
            <template x-for="user in assignees" :key="user.id">
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 transition hover:border-brand-200"
                       :class="selected.includes(String(user.id)) ? 'border-brand-300 bg-brand-50' : 'bg-white'">
                    <input type="checkbox" class="rounded text-brand-600 focus:ring-brand-500" :value="user.id" :checked="selected.includes(String(user.id))" @change="toggle(user.id)">
                    <span class="text-sm font-semibold" x-text="user.name"></span>
                </label>
            </template>
        </div>

        <template x-for="id in selected" :key="'hidden-' + id">
            <input type="hidden" name="assignee_ids[]" :value="id">
        </template>

        <p x-show="!projectId" class="mt-4 rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-muted">Select a project to choose assignees.</p>
        <p x-show="projectId && !assignees.length" class="mt-4 rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-muted">This project has no assigned users yet.</p>
    </div>
</div>
