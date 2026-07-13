@extends('layouts.guest')

@section('title', 'Change password')
@section('subtitle', 'For security, please set a new password before continuing')

@section('content')
    <form method="POST" action="{{ route('password.force.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label for="password" class="mb-1.5 block text-sm font-semibold">New password</label>
            <input id="password" type="password" name="password" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
        </div>

        <button type="submit" class="w-full rounded-2xl bg-brand-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-brand-600/25 transition hover:bg-brand-700">
            Save new password
        </button>
    </form>
@endsection
