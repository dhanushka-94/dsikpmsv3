@extends('layouts.app')

@section('title', 'Add project')
@section('page-title', 'Add project')
@section('page-subtitle', 'Create a new project and assign the team')

@section('content')
    <form method="POST" action="{{ route('projects.store') }}" class="mx-auto max-w-4xl space-y-6">
        @csrf
        @include('projects._form')
        <div class="flex flex-wrap gap-3">
            <button class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white hover:bg-brand-700">Create project</button>
            <a href="{{ route('projects.index') }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600">Cancel</a>
        </div>
    </form>
@endsection
