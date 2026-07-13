@extends('layouts.app')

@section('title', 'Add user')
@section('page-title', 'Add user')
@section('page-subtitle', 'Create a new system account')

@section('content')
    <form method="POST" action="{{ route('users.store') }}" enctype="multipart/form-data" class="mx-auto max-w-4xl space-y-6">
        @csrf
        @include('users._form')
        <div class="flex flex-wrap gap-3">
            <button class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white hover:bg-brand-700">Create user</button>
            <a href="{{ route('users.index') }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600">Cancel</a>
        </div>
    </form>
@endsection
