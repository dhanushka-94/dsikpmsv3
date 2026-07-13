@extends('layouts.app')

@section('title', 'Create task')
@section('page-title', 'Create task')
@section('page-subtitle', $selectedProject?->name ?? 'Add a task to a project')

@section('content')
    <form method="POST" action="{{ route('tasks.store') }}" class="mx-auto max-w-4xl space-y-6">
        @csrf
        @include('tasks._form')
        <div class="flex flex-wrap gap-3">
            <button class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white hover:bg-brand-700">Create task</button>
            @if($selectedProject)
                <a href="{{ route('projects.tasks.board', $selectedProject) }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600">Cancel</a>
            @else
                <a href="{{ route('projects.index') }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600">Cancel</a>
            @endif
        </div>
    </form>
@endsection
