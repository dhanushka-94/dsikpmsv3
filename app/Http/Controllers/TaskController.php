<?php

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function board(Project $project): View
    {
        $this->authorizeView($project);

        $project->load(['users', 'tasks.assignees']);

        $columns = collect(TaskStatus::boardColumns())->mapWithKeys(function (TaskStatus $status) use ($project) {
            return [
                $status->value => $project->tasks
                    ->where('status', $status)
                    ->sortBy('sort_order')
                    ->values(),
            ];
        });

        $this->activityLogger->forModel(
            action: 'board_viewed',
            subject: $project,
            description: 'Viewed task board for project "'.$project->name.'"',
            module: 'tasks',
            properties: ['tasks_count' => $project->tasks->count()],
        );

        return view('tasks.board', [
            'project' => $project,
            'columns' => $columns,
            'statuses' => TaskStatus::options(),
            'canManage' => $project->canBeEditedBy(auth()->user()),
        ]);
    }

    public function gantt(Project $project): View
    {
        $this->authorizeView($project);

        $project->load(['tasks.assignees']);

        $ganttTasks = $project->tasks
            ->sortBy('starts_at')
            ->values()
            ->map(fn (Task $task) => [
                'id' => (string) $task->id,
                'name' => $task->title,
                'start' => $task->starts_at->format('Y-m-d'),
                'end' => $task->ends_at->copy()->addDay()->format('Y-m-d'),
                'progress' => $task->status === TaskStatus::Done ? 100 : ($task->status === TaskStatus::Review ? 75 : ($task->status === TaskStatus::InProgress ? 40 : 0)),
                'custom_class' => 'priority-'.$task->priority->value,
            ]);

        $this->activityLogger->forModel(
            action: 'gantt_viewed',
            subject: $project,
            description: 'Viewed Gantt chart for project "'.$project->name.'"',
            module: 'tasks',
            properties: ['tasks_count' => $project->tasks->count()],
        );

        return view('tasks.gantt', [
            'project' => $project,
            'ganttTasks' => $ganttTasks,
            'tasks' => $project->tasks->sortBy('starts_at')->values(),
            'canManage' => $project->canBeEditedBy(auth()->user()),
        ]);
    }

    public function create(Request $request, ?Project $project = null): View
    {
        if ($project) {
            $this->authorizeEdit($project);
        } else {
            abort_unless(auth()->user()->canManageUsers(), 403);
        }

        return view('tasks.create', $this->formData($request, $project));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTask($request);
        $project = Project::with('users')->findOrFail($validated['project_id']);
        $this->authorizeEdit($project);

        $assigneeIds = $this->validatedAssigneeIds($request, $project);

        $task = Task::create([
            ...collect($validated)->except('assignee_ids')->all(),
            'status' => TaskStatus::Todo,
            'created_by' => $request->user()->id,
            'sort_order' => (int) Task::where('project_id', $project->id)->where('status', TaskStatus::Todo)->max('sort_order') + 1,
        ]);

        $task->assignees()->sync(
            collect($assigneeIds)->mapWithKeys(fn ($id) => [$id => ['is_enabled' => true]])->all()
        );

        $this->activityLogger->forModel(
            action: 'created',
            subject: $task,
            description: 'Created task "'.$task->title.'" on project "'.$project->name.'"',
            module: 'tasks',
            properties: [
                'attributes' => $this->activityLogger->snapshot($task),
                'project_id' => $project->id,
                'project_name' => $project->name,
                'assignees' => $this->activityLogger->pivotAssignments($task->assignees()->get(), ['is_enabled']),
            ],
        );

        return redirect()
            ->route('projects.tasks.board', $project)
            ->with('success', 'Task created successfully.');
    }

    public function edit(Task $task): View
    {
        $task->load(['project.users', 'assignees']);
        $this->authorizeEdit($task->project);

        return view('tasks.edit', array_merge(
            $this->formData(request(), $task->project, $task),
            compact('task')
        ));
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $task->load('project.users');
        $this->authorizeEdit($task->project);

        $validated = $this->validateTask($request, $task);
        $project = Project::with('users')->findOrFail($validated['project_id']);
        $this->authorizeEdit($project);

        $assigneeIds = $this->validatedAssigneeIds($request, $project);
        $before = $this->activityLogger->snapshot($task);
        $beforeAssignees = $this->activityLogger->pivotAssignments($task->assignees()->get(), ['is_enabled']);

        $task->update(collect($validated)->except('assignee_ids')->all());

        $existing = $task->assignees()->get()->keyBy('id');
        $sync = [];
        foreach ($assigneeIds as $id) {
            $sync[$id] = [
                'is_enabled' => (bool) ($existing->get($id)?->pivot?->is_enabled ?? true),
            ];
        }
        $task->assignees()->sync($sync);

        $this->activityLogger->forModel(
            action: 'updated',
            subject: $task,
            description: 'Updated task "'.$task->title.'"',
            module: 'tasks',
            properties: [
                ...$this->activityLogger->diff($before, $this->activityLogger->snapshot($task)),
                'project_id' => $project->id,
                'assignees_before' => $beforeAssignees,
                'assignees_after' => $this->activityLogger->pivotAssignments($task->assignees()->get(), ['is_enabled']),
            ],
        );

        return redirect()
            ->route('projects.tasks.board', $project)
            ->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->load('project');
        $this->authorizeEdit($task->project);

        $project = $task->project;
        $title = $task->title;
        $snapshot = $task->toArray();
        $task->delete();

        $this->activityLogger->log(
            action: 'deleted',
            description: 'Deleted task "'.$title.'"',
            module: 'tasks',
            properties: ['attributes' => $snapshot],
        );

        return redirect()
            ->route('projects.tasks.board', $project)
            ->with('success', 'Task deleted successfully.');
    }

    public function updateStatus(Request $request, Task $task): JsonResponse|RedirectResponse
    {
        $task->load('project');
        $this->authorizeEdit($task->project);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(TaskStatus::options()))],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $before = $task->status->value;

        $task->update([
            'status' => $validated['status'],
            'sort_order' => $validated['sort_order'] ?? $task->sort_order,
        ]);

        $this->activityLogger->forModel(
            action: 'status_changed',
            subject: $task,
            description: 'Moved task "'.$task->title.'" from '.$before.' to '.$validated['status'],
            module: 'tasks',
            properties: [
                'project_id' => $task->project_id,
                'project_name' => $task->project?->name,
                'from' => $before,
                'to' => $validated['status'],
                'sort_order' => $task->sort_order,
            ],
        );

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Task status updated.');
    }

    private function formData(Request $request, ?Project $project = null, ?Task $task = null): array
    {
        $user = $request->user();

        $projects = Project::query()
            ->with('users')
            ->when(! $user->canManageUsers(), function ($query) use ($user) {
                $query->whereHas('users', function ($q) use ($user) {
                    $q->where('users.id', $user->id)
                        ->where('project_user.permission', 'editor')
                        ->where('project_user.is_enabled', true);
                });
            })
            ->orderByDesc('year')
            ->orderBy('name')
            ->get();

        $selectedProject = $project ?? ($task?->project);

        $assignableUsers = $selectedProject
            ? $selectedProject->users->where('pivot.is_enabled', true)->sortBy('name')->values()
            : collect();

        if ($selectedProject && $assignableUsers->isEmpty() && $user->canManageUsers()) {
            $assignableUsers = \App\Models\User::query()
                ->where('role', '!=', 'super_admin')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        // Keep currently assigned people visible even if disabled on project
        if ($task) {
            $assignableUsers = $assignableUsers
                ->merge($task->assignees)
                ->unique('id')
                ->sortBy('name')
                ->values();
        }

        return [
            'projects' => $projects,
            'selectedProject' => $selectedProject,
            'assignableUsers' => $assignableUsers,
            'priorities' => TaskPriority::options(),
            'statuses' => TaskStatus::options(),
            'selectedAssignees' => old('assignee_ids', $task?->assignees->pluck('id')->all() ?? []),
        ];
    }

    private function validateTask(Request $request, ?Task $task = null): array
    {
        return $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'priority' => ['required', Rule::in(array_keys(TaskPriority::options()))],
            'status' => [$task ? 'required' : 'nullable', Rule::in(array_keys(TaskStatus::options()))],
            'description' => ['nullable', 'string', 'max:5000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'assignee_ids' => ['nullable', 'array'],
            'assignee_ids.*' => ['integer', 'exists:users,id'],
        ]);
    }

    private function validatedAssigneeIds(Request $request, Project $project): array
    {
        $ids = collect($request->input('assignee_ids', []))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $allowed = $project->users()
            ->wherePivot('is_enabled', true)
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (! empty($allowed)) {
            $ids = $ids->filter(fn ($id) => in_array($id, $allowed, true))->values();
        }

        return $ids->all();
    }

    public function toggleAssignee(Task $task, User $user): RedirectResponse
    {
        $task->load('project');
        $this->authorizeEdit($task->project);

        $assignment = $task->assignees()->where('users.id', $user->id)->first();

        if (! $assignment) {
            return back()->with('error', 'User is not assigned to this task.');
        }

        $enabled = ! (bool) $assignment->pivot->is_enabled;

        $task->assignees()->updateExistingPivot($user->id, [
            'is_enabled' => $enabled,
        ]);

        $this->activityLogger->forModel(
            action: $enabled ? 'assignee_enabled' : 'assignee_disabled',
            subject: $task,
            description: ($enabled ? 'Enabled' : 'Disabled').' '.$user->displayName().' on task "'.$task->title.'"',
            module: 'tasks',
            properties: [
                'user_id' => $user->id,
                'user_name' => $user->displayName(),
                'project_id' => $task->project_id,
                'is_enabled' => $enabled,
            ],
        );

        return back()->with('success', $user->displayName().' has been '.($enabled ? 'enabled' : 'disabled').' on this task.');
    }

    private function authorizeView(Project $project): void
    {
        abort_unless($project->canBeViewedBy(auth()->user()), 403);
    }

    private function authorizeEdit(Project $project): void
    {
        abort_unless($project->canBeEditedBy(auth()->user()), 403);
    }
}
