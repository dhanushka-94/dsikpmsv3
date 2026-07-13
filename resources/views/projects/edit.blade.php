@extends('layouts.app')

@section('title', 'Edit project')
@section('page-title', 'Edit project')
@section('page-subtitle', $project->name)

@section('content')
    <form method="POST" action="{{ route('projects.update', $project) }}" class="mx-auto max-w-4xl space-y-6">
        @csrf
        @method('PUT')
        @include('projects._form', ['project' => $project])
        <div class="flex flex-wrap gap-3">
            <button class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white hover:bg-brand-700">Save changes</button>
            <a href="{{ route('projects.show', $project) }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600">Cancel</a>
        </div>
    </form>
@endsection
