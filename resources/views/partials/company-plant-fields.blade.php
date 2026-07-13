@php
    $selectedCompanyId = (string) old($companyField ?? 'company_id', $selectedCompanyId ?? '');
    $selectedPlantId = (string) old($plantField ?? 'plant_id', $selectedPlantId ?? '');
    $plantsByCompany = collect($plants ?? [])->groupBy(fn ($plant) => (string) $plant->company_id)
        ->map(fn ($group) => $group->map(fn ($plant) => [
            'id' => (string) $plant->id,
            'name' => $plant->name,
        ])->values())
        ->all();
@endphp

<div
    class="contents"
    x-data="{
        companyId: @js($selectedCompanyId),
        plantId: @js($selectedPlantId),
        plantsByCompany: @js($plantsByCompany),
        get plants() {
            return this.plantsByCompany[this.companyId] || [];
        },
        onCompanyChange() {
            const allowed = this.plants.map(p => String(p.id));
            if (!allowed.includes(String(this.plantId))) {
                this.plantId = '';
            }
        }
    }"
>
    <div>
        <label class="mb-1.5 block text-sm font-semibold">Company <span class="text-brand-600">*</span></label>
        <select
            name="{{ $companyField ?? 'company_id' }}"
            x-model="companyId"
            @change="onCompanyChange()"
            required
            class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100"
        >
            <option value="">Select company</option>
            @foreach($companies as $company)
                <option value="{{ $company->id }}">{{ $company->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-semibold">Plant <span class="text-brand-600">*</span></label>
        <select
            name="{{ $plantField ?? 'plant_id' }}"
            x-model="plantId"
            required
            class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100"
            :disabled="!companyId || plants.length === 0"
        >
            <option value="">Select plant</option>
            <template x-for="plant in plants" :key="plant.id">
                <option :value="plant.id" x-text="plant.name" :selected="String(plantId) === String(plant.id)"></option>
            </template>
        </select>
        <p class="mt-1 text-xs text-muted" x-show="companyId && plants.length === 0">No plants for this company yet.</p>
    </div>
</div>
