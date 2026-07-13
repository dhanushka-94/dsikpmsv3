@extends('layouts.app')

@section('title', isset($plant) ? 'Edit plant' : 'Add plant')
@section('page-title', isset($plant) ? 'Edit plant' : 'Add plant')

@section('content')
    <form method="POST" action="{{ isset($plant) ? route('plants.update', $plant) : route('plants.store') }}" class="mx-auto max-w-2xl space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @isset($plant) @method('PUT') @endisset

        <div>
            <label class="mb-1.5 block text-sm font-semibold">Company <span class="text-brand-600">*</span></label>
            <select name="company_id" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                <option value="">Select company</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) old('company_id', $selectedCompanyId ?? $plant->company_id ?? '') === (string) $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-semibold">Plant name <span class="text-brand-600">*</span></label>
            <input type="text" name="name" value="{{ old('name', $plant->name ?? '') }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-semibold">Code</label>
            <input type="text" name="code" value="{{ old('code', $plant->code ?? '') }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-semibold">Sort order <span class="text-brand-600">*</span></label>
            <input type="number" name="sort_order" min="0" max="9999" value="{{ old('sort_order', $plant->sort_order ?? ($nextSortOrder ?? 1)) }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-semibold">Description</label>
            <textarea name="description" rows="3" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">{{ old('description', $plant->description ?? '') }}</textarea>
        </div>
        <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3">
            <input type="checkbox" name="is_active" value="1" class="rounded text-brand-600 focus:ring-brand-500" @checked(old('is_active', $plant->is_active ?? true))>
            <span class="text-sm font-semibold">Active</span>
        </label>

        <div class="flex gap-3">
            <button class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white hover:bg-brand-700">{{ isset($plant) ? 'Save changes' : 'Create plant' }}</button>
            <a href="{{ route('plants.index') }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600">Cancel</a>
        </div>
    </form>
@endsection
