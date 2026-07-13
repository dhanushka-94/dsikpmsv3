<?php

namespace App\Http\Controllers;

use App\Enums\ProjectPermission;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Department;
use App\Models\Plant;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $projects = Project::query()
            ->with(['category', 'department', 'company', 'plant', 'users.designation'])
            ->withCount(['users', 'tasks'])
            ->when(! $user->canManageUsers(), function ($query) use ($user) {
                $query->whereHas('users', fn ($q) => $q->where('users.id', $user->id)->where('project_user.is_enabled', true));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('year'), fn ($q) => $q->where('year', $request->integer('year')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('project_category_id'), fn ($q) => $q->where('project_category_id', $request->integer('project_category_id')))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->integer('company_id')))
            ->when($request->filled('plant_id'), fn ($q) => $q->where('plant_id', $request->integer('plant_id')))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('projects.index', [
            'projects' => $projects,
            'categories' => ProjectCategory::ordered()->get(),
            'departments' => Department::with('parent')->ordered()->get(),
            'companies' => Company::ordered()->get(),
            'years' => Project::yearOptions(),
            'statuses' => ProjectStatus::options(),
            'canManage' => $user->canManageUsers(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeManage();

        return view('projects.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage();

        $validated = $this->validateProject($request);
        $assignees = $this->validatedAssignees($request);

        $project = Project::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        $project->users()->sync($assignees);

        $this->activityLogger->forModel(
            action: 'created',
            subject: $project,
            description: 'Created project "'.$project->name.'"',
            module: 'projects',
            properties: [
                'attributes' => $this->activityLogger->snapshot($project),
                'assignees' => $this->activityLogger->pivotAssignments($project->users()->get()),
            ],
        );

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project created successfully.');
    }

    public function show(Project $project): View
    {
        $this->authorizeView($project);

        $project->load([
            'category',
            'company',
            'plant',
            'department.parent',
            'creator',
            'users.designation',
            'users.department',
            'tasks' => fn ($query) => $query->with(['assignees.designation'])->orderBy('sort_order')->orderBy('starts_at'),
        ]);

        $this->activityLogger->forModel(
            action: 'viewed',
            subject: $project,
            description: 'Viewed project "'.$project->name.'"',
            module: 'projects',
            properties: [
                'tasks_count' => $project->tasks->count(),
                'users_count' => $project->users->count(),
            ],
        );

        return view('projects.show', [
            'project' => $project,
            'canManage' => auth()->user()->canManageUsers(),
            'canEdit' => $project->canBeEditedBy(auth()->user()),
        ]);
    }

    public function edit(Project $project): View
    {
        $this->authorizeEdit($project);

        $project->load('users');

        return view('projects.edit', array_merge($this->formData($project), compact('project')));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeEdit($project);

        $validated = $this->validateProject($request, $project);
        $assignees = $this->validatedAssignees($request, $project);
        $before = $this->activityLogger->snapshot($project);
        $beforeAssignees = $this->activityLogger->pivotAssignments($project->users()->get());

        $project->update($validated);
        $project->users()->sync($assignees);

        $this->activityLogger->forModel(
            action: 'updated',
            subject: $project,
            description: 'Updated project "'.$project->name.'"',
            module: 'projects',
            properties: [
                ...$this->activityLogger->diff($before, $this->activityLogger->snapshot($project)),
                'assignees_before' => $beforeAssignees,
                'assignees_after' => $this->activityLogger->pivotAssignments($project->users()->get()),
            ],
        );

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeManage();

        $validated = $request->validate([
            'confirm_a' => ['required', 'integer', 'min:1', 'max:12'],
            'confirm_b' => ['required', 'integer', 'min:1', 'max:12'],
            'confirm_answer' => ['required', 'integer'],
        ]);

        if ((int) $validated['confirm_a'] + (int) $validated['confirm_b'] !== (int) $validated['confirm_answer']) {
            $this->activityLogger->forModel(
                action: 'delete_verification_failed',
                subject: $project,
                description: 'Failed math verification while deleting project "'.$project->name.'"',
                module: 'projects',
                properties: [
                    'confirm_a' => $validated['confirm_a'],
                    'confirm_b' => $validated['confirm_b'],
                    'confirm_answer' => $validated['confirm_answer'],
                ],
            );

            return back()->with('error', 'Incorrect verification answer. Project was not deleted.');
        }

        $name = $project->name;
        $snapshot = $project->toArray();
        $project->delete();

        $this->activityLogger->log(
            action: 'deleted',
            description: 'Deleted project "'.$name.'"',
            module: 'projects',
            properties: ['attributes' => $snapshot],
        );

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project deleted successfully.');
    }

    private function formData(?Project $project = null): array
    {
        $selectedAssignees = [];

        if ($project) {
            foreach ($project->users as $user) {
                $selectedAssignees[] = [
                    'user_id' => $user->id,
                    'permission' => $user->pivot->permission,
                    'is_enabled' => (bool) $user->pivot->is_enabled,
                ];
            }
        }

        return [
            'categories' => ProjectCategory::where('is_active', true)->ordered()->get(),
            'companies' => Company::where('is_active', true)->ordered()->get(),
            'plants' => Plant::where('is_active', true)->ordered()->get(['id', 'company_id', 'name']),
            'departments' => Department::with('parent')->where('is_active', true)->ordered()->get(),
            'assignableUsers' => User::query()
                ->where('role', '!=', UserRole::SuperAdmin->value)
                ->where('is_active', true)
                ->with(['designation', 'department'])
                ->orderBy('name')
                ->get(),
            'years' => Project::yearOptions(),
            'statuses' => ProjectStatus::options(),
            'permissions' => ProjectPermission::options(),
            'selectedAssignees' => collect(old('assignees', $selectedAssignees))->map(fn ($row) => [
                'user_id' => (string) $row['user_id'],
                'permission' => $row['permission'] ?? ProjectPermission::Viewer->value,
                'is_enabled' => filter_var($row['is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ])->values()->all(),
        ];
    }

    private function validateProject(Request $request, ?Project $project = null): array
    {
        $id = $project?->id;
        $currentYear = (int) now('Asia/Colombo')->year;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:'.($currentYear - 5), 'max:'.($currentYear + 5)],
            'project_category_id' => ['required', 'exists:project_categories,id'],
            'company_id' => ['required', 'exists:companies,id'],
            'plant_id' => ['required', 'exists:plants,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'reference_number' => ['nullable', 'string', 'max:100', 'unique:projects,reference_number,'.$id],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(array_keys(ProjectStatus::options()))],
        ]);

        Plant::assertBelongsToCompany(
            isset($validated['plant_id']) ? (int) $validated['plant_id'] : null,
            isset($validated['company_id']) ? (int) $validated['company_id'] : null,
        );

        if (empty($validated['reference_number'])) {
            $validated['reference_number'] = null;
        }

        return $validated;
    }

    private function validatedAssignees(Request $request, ?Project $project = null): array
    {
        $request->validate([
            'assignees' => ['nullable', 'array'],
            'assignees.*.user_id' => ['required', 'distinct', 'exists:users,id'],
            'assignees.*.permission' => ['required', Rule::in(array_keys(ProjectPermission::options()))],
            'assignees.*.is_enabled' => ['nullable', 'boolean'],
        ]);

        $existing = $project
            ? $project->users()->get()->keyBy('id')
            : collect();

        $assignees = [];

        foreach ($request->input('assignees', []) as $row) {
            $userId = (int) $row['user_id'];
            $user = User::find($userId);

            if ($user?->isSuperAdmin()) {
                throw ValidationException::withMessages([
                    'assignees' => 'Super Admin accounts cannot be assigned to projects.',
                ]);
            }

            $assignees[$userId] = [
                'permission' => $row['permission'] ?? ProjectPermission::Viewer->value,
                'is_enabled' => array_key_exists('is_enabled', $row)
                    ? filter_var($row['is_enabled'], FILTER_VALIDATE_BOOLEAN)
                    : (bool) ($existing->get($userId)?->pivot?->is_enabled ?? true),
            ];
        }

        return $assignees;
    }

    public function toggleUser(Request $request, Project $project, User $user): RedirectResponse
    {
        $this->authorizeEdit($project);

        $assignment = $project->users()->where('users.id', $user->id)->first();

        if (! $assignment) {
            return back()->with('error', 'User is not assigned to this project.');
        }

        $enabled = ! (bool) $assignment->pivot->is_enabled;

        $project->users()->updateExistingPivot($user->id, [
            'is_enabled' => $enabled,
        ]);

        $this->activityLogger->forModel(
            action: $enabled ? 'user_enabled' : 'user_disabled',
            subject: $project,
            description: ($enabled ? 'Enabled' : 'Disabled').' '.$user->displayName().' on project "'.$project->name.'"',
            module: 'projects',
            properties: [
                'user_id' => $user->id,
                'user_name' => $user->displayName(),
                'permission' => $assignment->pivot->permission,
                'is_enabled' => $enabled,
            ],
        );

        return back()->with('success', $user->displayName().' has been '.($enabled ? 'enabled' : 'disabled').'.');
    }

    private function authorizeManage(): void
    {
        if (! auth()->user()->canManageUsers()) {
            abort(403);
        }
    }

    private function authorizeView(Project $project): void
    {
        if (! $project->canBeViewedBy(auth()->user())) {
            abort(403);
        }
    }

    private function authorizeEdit(Project $project): void
    {
        if (! $project->canBeEditedBy(auth()->user())) {
            abort(403);
        }
    }
}
