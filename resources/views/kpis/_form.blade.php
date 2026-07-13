@php
    $kpi = $kpi ?? null;
    $projectOptions = $projects->map(fn ($p) => [
        'id' => (string) $p->id,
        'name' => $p->name,
        'meta' => collect([$p->year, $p->company?->name, $p->plant?->name])->filter()->implode(' · '),
    ])->values();
@endphp

<div class="space-y-6">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-base font-bold">KPI details</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-semibold">Index <span class="text-brand-600">*</span></label>
                <input type="text" name="kpi_index" value="{{ old('kpi_index', $kpi?->kpi_index) }}" required maxlength="100" placeholder="e.g. KPI-01 or Sales-A" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                <p class="mt-1 text-xs text-muted">Enter any index label manually (not auto-generated).</p>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-semibold">KPI name <span class="text-brand-600">*</span></label>
                <input type="text" name="name" value="{{ old('name', $kpi?->name) }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-semibold">Category <span class="text-brand-600">*</span></label>
                <select name="kpi_category_id" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    <option value="">Select category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('kpi_category_id', $kpi?->kpi_category_id) === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-muted"><a href="{{ route('kpi-categories.create') }}" class="font-semibold text-brand-700 hover:underline" target="_blank">Add KPI category</a></p>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-semibold">Benchmark type <span class="text-brand-600">*</span></label>
                <select name="benchmark_type" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    @foreach($benchmarkTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('benchmark_type', $kpi?->benchmark_type?->value ?? 'increase') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-semibold">Benchmark % <span class="text-brand-600">*</span></label>
                <input type="number" step="0.01" min="0" name="benchmark_percent" value="{{ old('benchmark_percent', $kpi?->benchmark_percent ?? 100) }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-semibold">Start date <span class="text-brand-600">*</span></label>
                <input type="date" name="start_date" value="{{ old('start_date', optional($kpi?->start_date)->format('Y-m-d')) }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-semibold">End date <span class="text-brand-600">*</span></label>
                <input type="date" name="end_date" value="{{ old('end_date', optional($kpi?->end_date)->format('Y-m-d')) }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            </div>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-semibold">Definition</label>
                <textarea name="definition" rows="3" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">{{ old('definition', $kpi?->definition) }}</textarea>
            </div>
        </div>
    </div>

    <div
        class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
        x-data="kpiFormulaBuilder(@js(old('formula', $kpi?->formula ?? '')), @js($formulaFields), @js(auth()->user()->isSuperAdmin()))"
    >
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-bold">Formula preset</h2>
                <p class="mt-1 text-sm text-muted">Add value names, build the formula, then save. Later open this KPI and fill only the value fields.</p>
            </div>
            <div class="rounded-2xl bg-brand-50 px-4 py-2 text-right">
                <p class="text-[11px] font-bold uppercase tracking-wider text-brand-700/70">Fields</p>
                <p class="text-sm font-extrabold text-brand-700" x-text="fields.length"></p>
            </div>
        </div>

        <input type="hidden" name="formula" :value="expression">
        <input type="hidden" name="formula_fields_payload" :value="JSON.stringify(fields.map((field) => field.name))">

        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
            <p class="text-sm font-bold">Value names</p>
            <p class="mt-1 text-xs text-muted">
                Create named fields first, then insert them into the formula.
                <span x-show="canEditValueNames" class="font-semibold text-brand-700">Super Admin can rename value names.</span>
            </p>

            <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                <input
                    type="text"
                    x-model="newFieldName"
                    @keydown.enter.prevent="addField()"
                    placeholder="Value name (e.g. Actual Sales)"
                    class="flex-1 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100"
                >
                <button type="button" class="rounded-2xl bg-ink px-4 py-3 text-sm font-bold text-white" @click="addField()">Add value name</button>
            </div>

            <div class="mt-4 space-y-2">
                <template x-for="(field, index) in fields" :key="'field-' + index">
                    <div class="flex flex-col gap-2 rounded-2xl border border-slate-200 bg-white p-3 sm:flex-row sm:items-center">
                        <input type="hidden" :name="'formula_fields[' + index + '][name]'" :value="field.name">
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Value name</p>
                            <template x-if="editingIndex !== index">
                                <p class="break-words font-semibold [overflow-wrap:anywhere]" x-text="field.name" :title="field.name"></p>
                            </template>
                            <template x-if="editingIndex === index">
                                <input
                                    type="text"
                                    x-model="editingName"
                                    @keydown.enter.prevent="saveFieldName(index)"
                                    @keydown.escape.prevent="cancelEditField()"
                                    class="mt-1 w-full rounded-xl border border-brand-300 bg-white px-3 py-2 text-sm font-semibold outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100"
                                >
                            </template>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="rounded-xl border border-brand-200 bg-brand-50 px-3 py-2 text-xs font-bold text-brand-700" @click="insertField(field.name)">Insert into formula</button>
                            <template x-if="canEditValueNames && editingIndex !== index">
                                <button type="button" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700" @click="startEditField(index)">Edit name</button>
                            </template>
                            <template x-if="canEditValueNames && editingIndex === index">
                                <button type="button" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700" @click="saveFieldName(index)">Save</button>
                            </template>
                            <template x-if="canEditValueNames && editingIndex === index">
                                <button type="button" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600" @click="cancelEditField()">Cancel</button>
                            </template>
                            <button type="button" class="rounded-xl border border-red-200 px-3 py-2 text-xs font-bold text-red-700" @click="requestRemoveField(index)">Remove</button>
                        </div>
                    </div>
                </template>
                <p x-show="fields.length === 0" class="rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-muted">No value names yet. Add at least one.</p>
            </div>
        </div>

        <div class="mt-5">
            <p class="mb-2 text-xs font-bold uppercase tracking-wider text-muted">Formula</p>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-sm font-semibold text-ink min-h-[3rem] break-words [overflow-wrap:anywhere]" x-text="expression || 'e.g. {Actual Sales} / {Target}'" :title="expression || ''"></div>
            <p class="mt-2 text-xs font-semibold text-red-600" x-show="error" x-text="error"></p>
        </div>

        <div class="mt-4">
            <p class="mb-2 text-xs font-bold uppercase tracking-wider text-muted">Operators</p>
            <div class="grid grid-cols-4 gap-2 sm:grid-cols-8">
                <template x-for="key in operators" :key="key">
                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm font-bold hover:border-brand-200 hover:bg-brand-50" @click="pressOperator(key)" x-text="key"></button>
                </template>
                <button type="button" class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-3 text-sm font-bold text-amber-800" @click="backspace()">⌫</button>
                <button type="button" class="rounded-xl border border-red-200 bg-red-50 px-3 py-3 text-sm font-bold text-red-700" @click="clearFormula()">Clear</button>
            </div>
        </div>

        <div class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50/70 p-4" x-show="fields.length">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="text-sm font-bold">Try with sample values</p>
                    <p class="text-xs text-muted">Preview only — real values are entered on the KPI page later.</p>
                </div>
                <div class="rounded-xl bg-white px-3 py-2 text-right shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-muted">Preview result</p>
                    <p class="text-lg font-extrabold text-brand-700" x-text="previewLabel"></p>
                </div>
            </div>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <template x-for="field in fields" :key="'sample-' + field.name">
                    <label class="block">
                        <span class="mb-1 block text-xs font-bold text-slate-600 break-words [overflow-wrap:anywhere]" x-text="field.name" :title="field.name"></span>
                        <input type="number" step="any" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" x-model="sampleValues[field.name]" @input="error = ''">
                    </label>
                </template>
            </div>
        </div>

        <p class="mt-3 text-xs text-muted">Example: add names <span class="font-semibold">Actual Sales</span> and <span class="font-semibold">Target</span>, then build <span class="font-semibold">{Actual Sales} / {Target}</span>.</p>
    </div>

    <div
        class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
        x-data="{
            projects: @js($projectOptions),
            selected: @js($selectedProjects),
            search: '',
            get available() {
                return this.projects.filter(p => !this.selected.includes(String(p.id)) && (!this.search || p.name.toLowerCase().includes(this.search.toLowerCase()) || (p.meta || '').toLowerCase().includes(this.search.toLowerCase())));
            },
            add(id) {
                if (!id || this.selected.includes(String(id))) return;
                this.selected.push(String(id));
                this.search = '';
            },
            remove(id) {
                this.selected = this.selected.filter(v => String(v) !== String(id));
            },
            requestRemoveProject(id) {
                const name = this.label(id);
                requestRemoveConfirm({
                    title: 'Remove this project?',
                    message: 'Remove ' + name + ' from this KPI assignment list.',
                    onConfirm: () => this.remove(id),
                });
            },
            label(id) {
                const project = this.projects.find(p => String(p.id) === String(id));
                return project ? project.name : 'Project';
            },
            meta(id) {
                const project = this.projects.find(p => String(p.id) === String(id));
                return project ? (project.meta || '') : '';
            }
        }"
    >
        <h2 class="text-base font-bold">Assign projects</h2>
        <p class="mt-1 text-sm text-muted">Link one or more projects to this KPI.</p>

        <div class="mt-4 grid gap-3 md:grid-cols-[1fr_auto]">
            <input type="text" x-model="search" placeholder="Search projects..." class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            <select class="rounded-2xl border border-slate-200 px-4 py-3 text-sm" @change="add($event.target.value); $event.target.value = ''">
                <option value="">Add project...</option>
                <template x-for="project in available" :key="project.id">
                    <option :value="project.id" x-text="project.name + (project.meta ? ' — ' + project.meta : '')"></option>
                </template>
            </select>
        </div>

        <div class="mt-4 space-y-2">
            <template x-for="id in selected" :key="id">
                <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                    <input type="hidden" name="project_ids[]" :value="id">
                    <div>
                        <p class="font-semibold" x-text="label(id)"></p>
                        <p class="text-xs text-muted" x-text="meta(id)"></p>
                    </div>
                    <button type="button" class="rounded-xl border border-red-200 px-3 py-1.5 text-xs font-bold text-red-700" @click="requestRemoveProject(id)">Remove</button>
                </div>
            </template>
            <p x-show="selected.length === 0" class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-muted">No projects assigned yet.</p>
        </div>
    </div>

    <div
        class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
        x-data="{
            users: @js($assignableUsers),
            assignments: @js($selectedAssignments),
            search: '',
            get available() {
                const selected = this.assignments.map(a => String(a.user_id));
                return this.users.filter(u => !selected.includes(String(u.id)) && u.available_weightage > 0 && (!this.search || u.name.toLowerCase().includes(this.search.toLowerCase()) || (u.meta || '').toLowerCase().includes(this.search.toLowerCase())));
            },
            userFor(id) {
                return this.users.find(u => String(u.id) === String(id));
            },
            availableFor(id) {
                const user = this.userFor(id);
                return user ? Number(user.available_weightage) : 0;
            },
            add(id) {
                if (!id) return;
                const available = this.availableFor(id);
                if (available <= 0) return;
                this.assignments.push({ user_id: String(id), weightage: Math.min(available, 100) });
                this.search = '';
            },
            remove(index) {
                this.assignments.splice(index, 1);
            },
            requestRemoveAssignment(index) {
                const assignment = this.assignments[index];
                const user = assignment ? this.userFor(assignment.user_id) : null;
                const name = (user && user.name) ? user.name : 'this user';
                requestRemoveConfirm({
                    title: 'Remove this user?',
                    message: 'Remove ' + name + ' from the weightage users list.',
                    onConfirm: () => this.remove(index),
                });
            },
            clamp(assignment) {
                const max = this.availableFor(assignment.user_id);
                let value = Number(assignment.weightage || 0);
                if (value > max) value = max;
                if (value < 0.01) value = 0.01;
                assignment.weightage = Number(value.toFixed(2));
            }
        }"
    >
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-bold">Weightage users</h2>
                <p class="mt-1 text-sm text-muted">Each user has 100% total weightage across all KPIs. Remaining capacity is shown per user.</p>
            </div>
            <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700" x-text="assignments.length + ' assigned'"></span>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-[1fr_auto]">
            <input type="text" x-model="search" placeholder="Search users..." class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            <select class="rounded-2xl border border-slate-200 px-4 py-3 text-sm" @change="add($event.target.value); $event.target.value = ''">
                <option value="">Add user...</option>
                <template x-for="user in available" :key="user.id">
                    <option :value="user.id" x-text="user.name + ' — ' + user.available_weightage + '% free'"></option>
                </template>
            </select>
        </div>

        <div class="mt-4 space-y-3">
            <template x-for="(assignment, index) in assignments" :key="assignment.user_id + '-' + index">
                <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 sm:flex-row sm:items-center">
                    <input type="hidden" :name="'assignments[' + index + '][user_id]'" :value="assignment.user_id">
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold" x-text="userFor(assignment.user_id)?.name || 'User'"></p>
                        <p class="text-xs text-muted">
                            <span x-text="userFor(assignment.user_id)?.meta || ''"></span>
                            · available <span class="font-bold" x-text="availableFor(assignment.user_id) + '%'"></span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="number" step="0.01" min="0.01" :max="availableFor(assignment.user_id)" :name="'assignments[' + index + '][weightage]'" x-model="assignment.weightage" @change="clamp(assignment)" class="w-28 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold">
                        <span class="text-sm font-bold text-muted">%</span>
                        <button type="button" class="rounded-xl border border-red-200 px-3 py-2 text-xs font-bold text-red-700" @click="requestRemoveAssignment(index)">Remove</button>
                    </div>
                </div>
            </template>
            <p x-show="assignments.length === 0" class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-muted">No users assigned yet.</p>
        </div>
    </div>

    <label class="flex items-center gap-3 rounded-3xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <input type="checkbox" name="is_active" value="1" class="rounded text-brand-600 focus:ring-brand-500" @checked(old('is_active', $kpi?->is_active ?? true))>
        <span class="text-sm font-semibold">Active KPI</span>
    </label>
</div>

@once
@push('scripts')
<script>
    function kpiFormulaBuilder(initialFormula, initialFields, canEditValueNames) {
        const fields = (initialFields || []).map((field) => ({ name: field.name }));
        const sampleValues = {};
        fields.forEach((field) => { sampleValues[field.name] = 1; });

        return {
            expression: initialFormula || '',
            error: '',
            newFieldName: '',
            fields,
            sampleValues,
            canEditValueNames: !!canEditValueNames,
            editingIndex: null,
            editingName: '',
            operators: ['+', '-', '*', '/', '%', '(', ')'],
            get previewLabel() {
                const value = this.evaluatePreview();
                return value === null ? '—' : value;
            },
            addField() {
                const name = (this.newFieldName || '').trim();
                if (!name) {
                    this.error = 'Enter a value name.';
                    return;
                }
                if (/[{}]/.test(name)) {
                    this.error = 'Value name cannot include { or }.';
                    return;
                }
                if (this.fields.some((field) => field.name.toLowerCase() === name.toLowerCase())) {
                    this.error = 'That value name already exists.';
                    return;
                }

                this.fields.push({ name });
                this.sampleValues[name] = 1;
                this.newFieldName = '';
                this.error = '';
            },
            startEditField(index) {
                if (!this.canEditValueNames) {
                    this.error = 'Only Super Admin can rename value names.';
                    return;
                }
                this.editingIndex = index;
                this.editingName = this.fields[index]?.name || '';
                this.error = '';
            },
            cancelEditField() {
                this.editingIndex = null;
                this.editingName = '';
            },
            saveFieldName(index) {
                if (!this.canEditValueNames) {
                    this.error = 'Only Super Admin can rename value names.';
                    return;
                }

                const oldName = this.fields[index]?.name || '';
                const newName = (this.editingName || '').trim();

                if (!newName) {
                    this.error = 'Enter a value name.';
                    return;
                }
                if (/[{}]/.test(newName)) {
                    this.error = 'Value name cannot include { or }.';
                    return;
                }
                if (this.fields.some((field, i) => i !== index && field.name.toLowerCase() === newName.toLowerCase())) {
                    this.error = 'That value name already exists.';
                    return;
                }

                if (oldName !== newName) {
                    const sample = this.sampleValues[oldName];
                    delete this.sampleValues[oldName];
                    this.sampleValues[newName] = sample ?? 1;
                    this.fields[index].name = newName;
                    this.expression = this.expression.split('{' + oldName + '}').join('{' + newName + '}');
                }

                this.editingIndex = null;
                this.editingName = '';
                this.error = '';
            },
            removeField(index) {
                const removed = this.fields[index];
                this.fields.splice(index, 1);
                if (this.editingIndex === index) {
                    this.cancelEditField();
                } else if (this.editingIndex !== null && this.editingIndex > index) {
                    this.editingIndex -= 1;
                }
                if (removed) {
                    delete this.sampleValues[removed.name];
                    this.expression = this.expression.split('{' + removed.name + '}').join('').replace(/\s{2,}/g, ' ').trim();
                }
            },
            requestRemoveField(index) {
                const field = this.fields[index];
                const name = (field && field.name) ? field.name : 'this value name';
                requestRemoveConfirm({
                    title: 'Remove this value name?',
                    message: 'Remove ' + name + ' from the formula preset. It will also be cleared from the formula if used.',
                    onConfirm: () => this.removeField(index),
                });
            },
            insertField(name) {
                this.appendToken('{' + name + '}');
            },
            pressOperator(key) {
                this.appendToken(key);
            },
            appendToken(token) {
                const trimmed = this.expression.trim();
                this.expression = (trimmed + ' ' + token).replace(/\s+/g, ' ').trim();
                this.error = '';
            },
            backspace() {
                const trimmed = this.expression.trim();
                if (!trimmed) return;

                const match = trimmed.match(/^(.*)(\{[^{}]+\}|[+\-*/%()]|\S+)$/);
                if (match) {
                    this.expression = match[1].trim();
                } else {
                    this.expression = '';
                }
                this.error = '';
            },
            clearFormula() {
                this.expression = '';
                this.error = '';
            },
            evaluatePreview() {
                if (!this.expression.trim() || !this.fields.length) return null;

                try {
                    let normalized = this.expression;
                    const placeholders = [...normalized.matchAll(/\{([^{}]+)\}/g)].map((match) => match[1].trim());

                    if (!placeholders.length) return null;

                    for (const name of placeholders) {
                        if (!this.fields.some((field) => field.name === name)) {
                            this.error = '"' + name + '" is not in your value names.';
                            return null;
                        }

                        const raw = this.sampleValues[name];
                        if (raw === '' || raw === null || raw === undefined || isNaN(Number(raw))) {
                            this.error = 'Enter a sample value for ' + name;
                            return null;
                        }

                        normalized = normalized.split('{' + name + '}').join(String(Number(raw)));
                    }

                    if (/[0-9]/.test(normalized.replace(/[0-9.+\-*/()%\s]/g, '')) === false && /\{/.test(normalized)) {
                        this.error = 'Invalid formula preset';
                        return null;
                    }

                    normalized = normalized.replace(/(\d+(?:\.\d+)?)\s*%\s*(\d+(?:\.\d+)?)/g, '((($1/100)*$2))');
                    normalized = normalized.replace(/(\d+(?:\.\d+)?)\s*%/g, '($1/100)');

                    if (!/^[0-9+\-*/().\s]+$/.test(normalized)) {
                        this.error = 'Invalid formula preset';
                        return null;
                    }

                    // eslint-disable-next-line no-new-func
                    const value = Function('"use strict"; return (' + normalized + ')')();
                    if (typeof value !== 'number' || !isFinite(value)) {
                        this.error = 'Invalid preview result';
                        return null;
                    }

                    this.error = '';
                    return Math.round(value * 10000) / 10000;
                } catch (e) {
                    this.error = 'Invalid formula preset';
                    return null;
                }
            }
        }
    }
</script>
@endpush
@endonce
