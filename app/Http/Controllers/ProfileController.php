<?php

namespace App\Http\Controllers;

use App\Enums\UserTitle;
use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}
    public function edit(): View
    {
        $user = auth()->user()->load(['department', 'designation', 'parent']);

        return view('profile.edit', [
            'user' => $user,
            'departments' => Department::where('is_active', true)->with('parent')->ordered()->get(),
            'designations' => Designation::where('is_active', true)->ordered()->get(),
            'parentUsers' => User::query()
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->orderBy('name')
                ->get()
                ->map(fn (User $u) => (object) [
                    'id' => $u->id,
                    'name' => $u->displayName().' ('.$u->email.')',
                ]),
            'titles' => UserTitle::options(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => ['required', Rule::in(array_keys(UserTitle::options()))],
            'name' => ['required', 'string', 'max:255'],
            'epf_number' => ['nullable', 'string', 'max:50', 'unique:users,epf_number,'.$user->id],
            'department_id' => ['required', 'exists:departments,id'],
            'designation_id' => ['required', 'exists:designations,id'],
            'parent_user_id' => ['nullable', 'exists:users,id', Rule::notIn([$user->id])],
            'profile_picture' => ['nullable', 'image', 'max:2048'],
            'remove_profile_picture' => ['nullable', 'boolean'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        if (empty($validated['epf_number'])) {
            $validated['epf_number'] = null;
        }

        if (empty($validated['parent_user_id'])) {
            $validated['parent_user_id'] = null;
        }

        if ($request->hasFile('profile_picture')) {
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $validated['profile_picture'] = $request->file('profile_picture')
                ->store('profiles', 'public');
        }

        if ($request->boolean('remove_profile_picture') && $user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
            $validated['profile_picture'] = null;
        }

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
            $validated['must_change_password'] = false;
        } else {
            unset($validated['password']);
        }

        unset($validated['current_password'], $validated['remove_profile_picture']);

        $passwordChanged = array_key_exists('password', $validated);
        $before = $this->activityLogger->snapshot($user);
        $user->update($validated);

        $properties = $this->activityLogger->diff($before, $this->activityLogger->snapshot($user));
        if ($passwordChanged) {
            $properties['password_changed'] = true;
        }

        $this->activityLogger->forModel(
            action: 'profile_updated',
            subject: $user,
            description: $user->displayName().' updated their profile',
            module: 'profile',
            properties: $properties,
        );

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Profile updated successfully.');
    }
}
