<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $this->activityLogger->log(
                action: 'login_failed',
                description: 'Failed login attempt for '.$credentials['email'],
                module: 'auth',
                properties: ['email' => $credentials['email']],
            );

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            $this->activityLogger->log(
                action: 'login_blocked',
                description: 'Inactive account login blocked for '.$user->email,
                module: 'auth',
                subject: $user,
                properties: ['email' => $user->email],
                actor: $user,
            );

            throw ValidationException::withMessages([
                'email' => 'Your account is inactive. Contact an administrator.',
            ]);
        }

        $request->session()->regenerate();

        $this->activityLogger->log(
            action: 'login',
            description: $user->displayName().' signed in',
            module: 'auth',
            subject: $user,
            actor: $user,
        );

        if ($user->must_change_password) {
            return redirect()->route('password.force.edit');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            $this->activityLogger->log(
                action: 'logout',
                description: $user->displayName().' signed out',
                module: 'auth',
                subject: $user,
                actor: $user,
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
