@extends('layouts.app')

@section('title', isset($category) ? 'Edit KPI category' : 'Add KPI category')
@section('page-title', isset($category) ? 'Edit KPI category' : 'Add KPI category')

@section('content')
    <form method="POST" action="{{ isset($category) ? route('kpi-categories.update', $category) : route('kpi-categories.store') }}" class="mx-auto max-w-2xl space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @isset($category) @method('PUT') @endisset

        <div>
            <label class="mb-1.5 block text-sm font-semibold">Name <span class="text-brand-600">*</span></label>
            <input type="text" name="name" value="{{ old('name', $category->name ?? '') }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-semibold">Code</label>
            <input type="text" name="code" value="{{ old('code', $category->code ?? '') }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-semibold">Sort order <span class="text-brand-600">*</span></label>
            <input type="number" name="sort_order" min="0" max="9999" value="{{ old('sort_order', $category->sort_order ?? ($nextSortOrder ?? 1)) }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-semibold">Description</label>
            <textarea name="description" rows="3" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">{{ old('description', $category->description ?? '') }}</textarea>
        </div>
        <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3">
            <input type="checkbox" name="is_active" value="1" class="rounded text-brand-600 focus:ring-brand-500" @checked(old('is_active', $category->is_active ?? true))>
            <span class="text-sm font-semibold">Active</span>
        </label>

        <div class="flex gap-3">
            <button class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white hover:bg-brand-700">{{ isset($category) ? 'Save changes' : 'Create category' }}</button>
            <a href="{{ route('kpi-categories.index') }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600">Cancel</a>
        </div>
    </form>
@endsection
