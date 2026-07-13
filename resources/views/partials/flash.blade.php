@if (session('success'))
    <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <p class="font-semibold">Please fix the following:</p>
        <ul class="mt-1 list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session('temp_credentials'))
    @php $creds = session('temp_credentials'); @endphp
    <div
        class="mb-4 rounded-2xl border border-brand-200 bg-brand-50 px-4 py-4 text-sm text-brand-900"
        x-data="{ copied: false, text: @js('Email: '.$creds['email']."\nTemporary Password: ".$creds['password']."\nLogin URL: ".route('login')) }"
    >
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="font-bold">Temporary login credentials</p>
                <p class="mt-1 text-brand-800/80">Share these securely. The user must change the password on first login.</p>
                @if (!empty($creds['emailed']))
                    <p class="mt-1 text-xs font-semibold text-emerald-700">Also sent by email.</p>
                @endif
            </div>
            <button
                type="button"
                class="rounded-xl bg-brand-600 px-3 py-2 text-xs font-bold text-white hover:bg-brand-700"
                @click="navigator.clipboard.writeText(text); copied = true; setTimeout(() => copied = false, 2000)"
            >
                <span x-text="copied ? 'Copied!' : 'Copy info'"></span>
            </button>
        </div>
        <div class="mt-3 space-y-1 rounded-xl bg-white/80 p-3 font-mono text-xs">
            <p><span class="text-muted">Email:</span> {{ $creds['email'] }}</p>
            <p><span class="text-muted">Password:</span> {{ $creds['password'] }}</p>
            <p><span class="text-muted">Login:</span> {{ route('login') }}</p>
        </div>
    </div>
@endif
