@extends('layouts.app')

@section('title', 'Add KPI')
@section('page-title', 'Add KPI')
@section('page-subtitle', 'Create a new key performance indicator')

@section('content')
    <form method="POST" action="{{ route('kpis.store') }}" class="mx-auto max-w-4xl space-y-6">
        @csrf
        @include('kpis._form')
        <div class="flex flex-wrap gap-3">
            <button class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white hover:bg-brand-700">Create KPI</button>
            <a href="{{ route('kpis.index') }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600">Cancel</a>
        </div>
    </form>
@endsection
