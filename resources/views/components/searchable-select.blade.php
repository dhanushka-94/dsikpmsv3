@props([
    'name',
    'options',
    'selected' => null,
    'placeholder' => 'Select an option',
    'label' => null,
    'required' => false,
    'optionLabel' => 'name',
    'optionValue' => 'id',
])

@php
    $items = collect($options)->map(function ($item) use ($optionLabel, $optionValue) {
        if (is_array($item)) {
            return ['value' => (string) $item[$optionValue], 'label' => $item[$optionLabel]];
        }
        if (is_object($item)) {
            return ['value' => (string) $item->{$optionValue}, 'label' => $item->{$optionLabel}];
        }
        return ['value' => (string) $item, 'label' => (string) $item];
    })->values();
    $selectedValue = old($name, $selected);
@endphp

<div
    x-data="{
        open: false,
        search: '',
        selected: @js($selectedValue ? (string) $selectedValue : ''),
        options: @js($items),
        get filtered() {
            if (!this.search) return this.options;
            return this.options.filter(o => o.label.toLowerCase().includes(this.search.toLowerCase()));
        },
        get label() {
            const match = this.options.find(o => o.value === this.selected);
            return match ? match.label : @js($placeholder);
        },
        choose(value) {
            this.selected = value;
            this.open = false;
            this.search = '';
        }
    }"
    class="relative"
    @click.outside="open = false"
>
    @if($label)
        <label class="mb-1.5 block text-sm font-semibold">{{ $label }} @if($required)<span class="text-brand-600">*</span>@endif</label>
    @endif

    <input type="hidden" name="{{ $name }}" :value="selected" @if($required) required @endif>

    <button
        type="button"
        @click="open = !open"
        class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"
    >
        <span :class="selected ? 'text-ink font-medium' : 'text-muted'" x-text="label"></span>
        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>

    <div
        x-show="open"
        x-transition
        class="absolute z-20 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
    >
        <div class="border-b border-slate-100 p-2">
            <input
                type="text"
                x-model="search"
                placeholder="Search..."
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500"
                @click.stop
            >
        </div>
        <ul class="max-h-56 overflow-y-auto py-1">
            <template x-for="option in filtered" :key="option.value">
                <li>
                    <button
                        type="button"
                        class="flex w-full px-4 py-2 text-left text-sm hover:bg-brand-50"
                        :class="selected === option.value ? 'bg-brand-50 font-semibold text-brand-700' : ''"
                        @click="choose(option.value)"
                        x-text="option.label"
                    ></button>
                </li>
            </template>
            <li x-show="filtered.length === 0" class="px-4 py-3 text-sm text-muted">No results found</li>
        </ul>
    </div>
</div>
