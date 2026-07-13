<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Enums\UserTitle;
use App\Mail\TemporaryPasswordMail;
use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\TemporaryPasswordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private TemporaryPasswordService $passwordService,
        private ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): View
    {
        $users = User::query()
            ->with(['department', 'designation', 'parent'])
            ->withCount([
                'projects as projects_count' => function ($query) {
                    $query->where('project_user.is_enabled', true);
                },
                'tasks as tasks_count' => function ($query) {
                    $query->where('task_user.is_enabled', true);
                },
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('epf_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->string('status') === 'active');
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $departments = Department::ordered()->get();

        return view('users.index', compact('users', 'departments'));
    }

    public function create(): View
    {
        return view('users.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateUser($request);
        $temporaryPassword = $this->passwordService->generate();

        if ($request->hasFile('profile_picture')) {
            $validated['profile_picture'] = $request->file('profile_picture')
                ->store('profiles', 'public');
        }

        $validated['password'] = Hash::make($temporaryPassword);
        $validated['must_change_password'] = true;
        $validated['is_active'] = $request->boolean('is_active', true);

        $user = User::create($validated);

        $emailed = false;
        if ($request->boolean('send_credentials_email')) {
            Mail::to($user->email)->send(new TemporaryPasswordMail($user, $temporaryPassword));
            $emailed = true;
        }

        $this->activityLogger->forModel(
            action: 'created',
            subject: $user,
            description: 'Created user '.$user->displayName().' ('.$user->email.')',
            module: 'users',
            properties: [
                'email' => $user->email,
                'role' => $user->role->value,
                'credentials_emailed' => $emailed,
            ],
        );

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'User created successfully.')
            ->with('temp_credentials', [
                'email' => $user->email,
                'password' => $temporaryPassword,
                'emailed' => $emailed,
            ]);
    }

    public function show(User $user): View
    {
        return $this->renderProfile($user, manage: true);
    }

    public function profile(User $user): View
    {
        return $this->renderProfile($user, manage: auth()->user()->canManageUsers());
    }

    private function renderProfile(User $user, bool $manage): View
    {
        if ($user->isSuperAdmin() && ! auth()->user()->isSuperAdmin()) {
            abort(404);
        }

        $user->load(['department.parent', 'designation', 'parent']);

        $projects = $user->projects()
            ->wherePivot('is_enabled', true)
            ->with(['category', 'department'])
            ->orderByDesc('year')
            ->orderBy('name')
            ->get();

        $tasks = $user->tasks()
            ->wherePivot('is_enabled', true)
            ->with(['project'])
            ->orderByDesc('starts_at')
            ->get();

        $projectStats = [
            'total' => $projects->count(),
            'ongoing' => $projects->filter(fn ($project) => $project->status === ProjectStatus::Ongoing)->count(),
            'on_hold' => $projects->filter(fn ($project) => $project->status === ProjectStatus::OnHold)->count(),
            'completed' => $projects->filter(fn ($project) => $project->status === ProjectStatus::Completed)->count(),
        ];
        $projectStats['percent'] = $projectStats['total'] > 0
            ? (int) round(($projectStats['completed'] / $projectStats['total']) * 100)
            : 0;

        $taskStats = [
            'total' => $tasks->count(),
            'todo' => $tasks->filter(fn ($task) => $task->status === TaskStatus::Todo)->count(),
            'in_progress' => $tasks->filter(fn ($task) => $task->status === TaskStatus::InProgress)->count(),
            'review' => $tasks->filter(fn ($task) => $task->status === TaskStatus::Review)->count(),
            'done' => $tasks->filter(fn ($task) => $task->status === TaskStatus::Done)->count(),
        ];
        $taskStats['percent'] = $taskStats['total'] > 0
            ? (int) round(($taskStats['done'] / $taskStats['total']) * 100)
            : 0;

        $user->projects_count = $projectStats['total'];
        $user->tasks_count = $taskStats['total'];

        return view('users.show', [
            'user' => $user,
            'projects' => $projects,
            'tasks' => $tasks,
            'projectStats' => $projectStats,
            'taskStats' => $taskStats,
            'canManage' => $manage && auth()->user()->canManageUsers(),
        ]);
    }

    public function edit(User $user): View
    {
        $this->authorizeUserEdit($user);

        return view('users.edit', array_merge($this->formData($user), compact('user')));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUserEdit($user);

        $validated = $this->validateUser($request, $user);
        $validated['is_active'] = $request->boolean('is_active');

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

        $before = $this->activityLogger->snapshot($user);
        $user->update($validated);

        $this->activityLogger->forModel(
            action: 'updated',
            subject: $user,
            description: 'Updated user '.$user->displayName().' ('.$user->email.')',
            module: 'users',
            properties: $this->activityLogger->diff($before, $this->activityLogger->snapshot($user)),
        );

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->isSuperAdmin() && ! auth()->user()->isSuperAdmin()) {
            return back()->with('error', 'Only Super Admins can delete Super Admin accounts.');
        }

        if ($user->children()->exists()) {
            return back()->with('error', 'Cannot delete a user who is a parent of other users.');
        }

        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        $label = $user->displayName().' ('.$user->email.')';
        $snapshot = collect($user->toArray())->except(['password', 'remember_token'])->all();
        $user->delete();

        $this->activityLogger->log(
            action: 'deleted',
            description: 'Deleted user '.$label,
            module: 'users',
            properties: ['attributes' => $snapshot],
        );

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        if (! auth()->user()->canResetPasswords()) {
            abort(403);
        }

        if ($user->isSuperAdmin() && ! auth()->user()->isSuperAdmin()) {
            return back()->with('error', 'Only Super Admins can reset Super Admin passwords.');
        }

        $temporaryPassword = $this->passwordService->generate();

        $user->update([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
        ]);

        $emailed = false;
        if ($request->boolean('send_email')) {
            Mail::to($user->email)->send(new TemporaryPasswordMail($user, $temporaryPassword, true));
            $emailed = true;
        }

        $this->activityLogger->forModel(
            action: 'password_reset',
            subject: $user,
            description: 'Reset password for '.$user->displayName().' ('.$user->email.')',
            module: 'users',
            properties: [
                'email' => $user->email,
                'credentials_emailed' => $emailed,
            ],
        );

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'Password reset successfully.')
            ->with('temp_credentials', [
                'email' => $user->email,
                'password' => $temporaryPassword,
                'emailed' => $emailed,
            ]);
    }

    private function formData(?User $exclude = null): array
    {
        return [
            'departments' => Department::where('is_active', true)->with('parent')->ordered()->get(),
            'designations' => Designation::where('is_active', true)->ordered()->get(),
            'parentUsers' => User::query()
                ->where('is_active', true)
                ->when($exclude, fn ($q) => $q->where('id', '!=', $exclude->id))
                ->orderBy('name')
                ->get()
                ->map(fn (User $u) => (object) [
                    'id' => $u->id,
                    'name' => $u->displayName().' ('.$u->email.')',
                ]),
            'titles' => UserTitle::options(),
            'roles' => $this->availableRoles(),
        ];
    }

    private function availableRoles(): array
    {
        $roles = UserRole::options();

        if (! auth()->user()->isSuperAdmin()) {
            unset($roles[UserRole::SuperAdmin->value]);
        }

        return $roles;
    }

    private function authorizeUserEdit(User $user): void
    {
        if ($user->isSuperAdmin() && ! auth()->user()->isSuperAdmin()) {
            abort(403, 'Only Super Admins can edit Super Admin accounts.');
        }
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        $id = $user?->id;
        $roleRules = ['required', Rule::in(array_keys($this->availableRoles()))];

        if ($user?->isSuperAdmin() && auth()->user()->isSuperAdmin()) {
            $roleRules = ['required', Rule::in(array_keys(UserRole::options()))];
        }

        $validated = $request->validate([
            'title' => ['required', Rule::in(array_keys(UserTitle::options()))],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$id],
            'epf_number' => ['nullable', 'string', 'max:50', 'unique:users,epf_number,'.$id],
            'department_id' => ['required', 'exists:departments,id'],
            'designation_id' => ['required', 'exists:designations,id'],
            'role' => $roleRules,
            'parent_user_id' => ['nullable', 'exists:users,id', Rule::notIn(array_filter([$id]))],
            'profile_picture' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
            'send_credentials_email' => ['nullable', 'boolean'],
            'remove_profile_picture' => ['nullable', 'boolean'],
        ]);

        unset($validated['send_credentials_email'], $validated['remove_profile_picture']);

        if (empty($validated['parent_user_id'])) {
            $validated['parent_user_id'] = null;
        }

        if (empty($validated['epf_number'])) {
            $validated['epf_number'] = null;
        }

        return $validated;
    }
}
