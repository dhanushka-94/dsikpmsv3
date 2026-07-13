<x-mail::message>
# {{ $isReset ? 'Password Reset' : 'Welcome to DSI KPI Monitoring System' }}

Hello {{ $user->displayName() }},

@if ($isReset)
Your password has been reset by an administrator.
@else
An account has been created for you on the **DSI KPI Monitoring System**.
@endif

**Login email:** {{ $user->email }}  
**Temporary password:** {{ $temporaryPassword }}

Please sign in and change your password on first visit.

<x-mail::button :url="route('login')">
Sign in
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
