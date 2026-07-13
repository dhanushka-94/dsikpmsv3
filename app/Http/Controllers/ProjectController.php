<?php

namespace App\Http\Controllers;

use App\Enums\ProjectPermission;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\Department;
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
            ->with(['category', 'department', 'users'])
            ->withCount('users')
            ->when(! $user->canManageUsers(), function ($query) use ($user) {
                $query->whereHas('users', fn ($q) => $q->where('users.id', $user->id));
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
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('projects.index', [
            'projects' => $projects,
            'categories' => ProjectCategory::ordered()->get(),
            'departments' => Department::with('parent')->ordered()->get(),
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
                'attributes' => $project->toArray(),
                'assignees' => $assignees,
            ],
        );

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project created successfully.');
    }

    public function show(Project $project): View
    {
        $this->authorizeView($project);

        $project->load(['category', 'department.parent', 'creator', 'users.designation', 'users.department']);

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
        $assignees = $this->validatedAssignees($request);
        $before = $this->activityLogger->snapshot($project);

        $project->update($validated);
        $project->users()->sync($assignees);

        $this->activityLogger->forModel(
            action: 'updated',
            subject: $project,
            description: 'Updated project "'.$project->name.'"',
            module: 'projects',
            properties: [
                ...$this->activityLogger->diff($before, $this->activityLogger->snapshot($project)),
                'assignees' => $assignees,
            ],
        );

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorizeManage();

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
                ];
            }
        }

        return [
            'categories' => ProjectCategory::where('is_active', true)->ordered()->get(),
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
            'selectedAssignees' => old('assignees', $selectedAssignees),
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
            'department_id' => ['required', 'exists:departments,id'],
            'reference_number' => ['nullable', 'string', 'max:100', 'unique:projects,reference_number,'.$id],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(array_keys(ProjectStatus::options()))],
        ]);

        if (empty($validated['reference_number'])) {
            $validated['reference_number'] = null;
        }

        return $validated;
    }

    private function validatedAssignees(Request $request): array
    {
        $request->validate([
            'assignees' => ['nullable', 'array'],
            'assignees.*.user_id' => ['required', 'distinct', 'exists:users,id'],
            'assignees.*.permission' => ['required', Rule::in(array_keys(ProjectPermission::options()))],
        ]);

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
            ];
        }

        return $assignees;
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
