@extends('layouts.app')

@section('title', 'Edit KPI')
@section('page-title', 'Edit KPI')
@section('page-subtitle', $kpi->name)

@section('content')
    <form method="POST" action="{{ route('kpis.update', $kpi) }}" class="mx-auto max-w-4xl space-y-6">
        @csrf
        @method('PUT')
        @include('kpis._form', ['kpi' => $kpi])
        <div class="flex flex-wrap gap-3">
            <button class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white hover:bg-brand-700">Save changes</button>
            <a href="{{ route('kpis.show', $kpi) }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600">Cancel</a>
        </div>
    </form>
@endsection
