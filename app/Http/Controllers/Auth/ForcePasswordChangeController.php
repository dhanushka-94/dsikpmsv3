<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ForcePasswordChangeController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function edit(): View
    {
        return view('auth.force-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        $this->activityLogger->forModel(
            action: 'password_changed',
            subject: $user,
            description: $user->displayName().' changed password on first login',
            module: 'auth',
        );

        return redirect()
            ->route('dashboard')
            ->with('success', 'Password updated successfully. Welcome aboard!');
    }
}
