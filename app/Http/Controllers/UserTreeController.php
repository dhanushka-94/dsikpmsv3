<?php

namespace App\Http\Controllers;

use App\Enums\ProjectPermission;
use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class UserTreeController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function __invoke(Request $request): View
    {
        $departmentId = $request->integer('department_id') ?: null;
        $designationId = $request->integer('designation_id') ?: null;

        $users = User::query()
            ->with(['department.parent', 'designation', 'parent'])
            ->withCount([
                'projects as projects_count' => function ($query) {
                    $query->where('project_user.is_enabled', true);
                },
                'tasks as tasks_count' => function ($query) {
                    $query->where('task_user.is_enabled', true);
                },
            ])
            ->where('role', '!=', 'super_admin')
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($designationId, fn ($q) => $q->where('designation_id', $designationId))
            ->get();

        $visibleIds = $users->pluck('id')->map(fn ($id) => (int) $id)->all();
        $roots = $this->buildTree($users, $visibleIds);

        $this->activityLogger->log(
            action: 'viewed',
            description: 'Viewed users tree',
            module: 'users_tree',
            properties: [
                'total_users' => $users->count(),
                'filters' => [
                    'department_id' => $departmentId,
                    'designation_id' => $designationId,
                ],
            ],
        );

        return view('users.tree', [
            'roots' => $roots,
            'totalUsers' => $users->count(),
            'departments' => Department::with('parent')->ordered()->get(),
            'designations' => Designation::ordered()->get(),
            'filters' => [
                'department_id' => $departmentId,
                'designation_id' => $designationId,
            ],
        ]);
    }

    public function projects(User $user): JsonResponse
    {
        $this->authorizeTreeUser($user);

        $items = $user->projects()
            ->wherePivot('is_enabled', true)
            ->with(['category', 'department'])
            ->orderByDesc('year')
            ->orderBy('name')
            ->get()
            ->map(fn ($project) => [
                'id' => $project->id,
                'title' => $project->name,
                'meta' => collect([
                    $project->year,
                    $project->reference_number,
                    $project->category?->name,
                ])->filter()->implode(' · '),
                'badge' => $project->status->label(),
                'badge_class' => $project->status->badgeClasses(),
                'dates' => collect([
                    optional($project->start_date)->format('Y-m-d'),
                    optional($project->end_date)->format('Y-m-d'),
                ])->filter()->implode(' → ') ?: null,
                'extra' => ProjectPermission::from($project->pivot->permission)->label(),
                'url' => route('projects.show', $project),
            ]);

        $this->activityLogger->forModel(
            action: 'assignments_viewed',
            subject: $user,
            description: 'Viewed project assignments for '.$user->displayName(),
            module: 'users_tree',
            properties: [
                'type' => 'projects',
                'count' => $items->count(),
            ],
        );

        return response()->json([
            'type' => 'projects',
            'user' => $user->displayName(),
            'items' => $items,
        ]);
    }

    public function tasks(User $user): JsonResponse
    {
        $this->authorizeTreeUser($user);

        $items = $user->tasks()
            ->wherePivot('is_enabled', true)
            ->with(['project'])
            ->orderBy('starts_at')
            ->get()
            ->map(fn ($task) => [
                'id' => $task->id,
                'title' => $task->title,
                'meta' => $task->project?->name,
                'badge' => $task->status->label(),
                'badge_class' => $task->status->badgeClasses(),
                'dates' => $task->starts_at->format('Y-m-d H:i').' → '.$task->ends_at->format('Y-m-d H:i'),
                'extra' => $task->priority->label(),
                'url' => $task->project
                    ? route('projects.tasks.board', $task->project)
                    : null,
            ]);

        $this->activityLogger->forModel(
            action: 'assignments_viewed',
            subject: $user,
            description: 'Viewed task assignments for '.$user->displayName(),
            module: 'users_tree',
            properties: [
                'type' => 'tasks',
                'count' => $items->count(),
            ],
        );

        return response()->json([
            'type' => 'tasks',
            'user' => $user->displayName(),
            'items' => $items,
        ]);
    }

    private function authorizeTreeUser(User $user): void
    {
        abort_if($user->isSuperAdmin(), 404);
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  array<int, int>  $visibleIds
     * @return Collection<int, array{user: User, children: Collection}>
     */
    private function buildTree(Collection $users, array $visibleIds): Collection
    {
        $byParent = $users->groupBy(function (User $user) use ($visibleIds) {
            $parentId = $user->parent_user_id ? (int) $user->parent_user_id : null;

            // Missing parent or parent is Super Admin / filtered out => treat as root.
            if (! $parentId || ! in_array($parentId, $visibleIds, true)) {
                return 'root';
            }

            return (string) $parentId;
        });

        $sortSiblings = function (Collection $siblings): Collection {
            return $siblings->sort(function (User $a, User $b) {
                $orderA = $a->designation?->sort_order ?? PHP_INT_MAX;
                $orderB = $b->designation?->sort_order ?? PHP_INT_MAX;

                if ($orderA !== $orderB) {
                    return $orderA <=> $orderB;
                }

                return strcasecmp($a->name, $b->name);
            })->values();
        };

        $build = function (string $parentKey) use (&$build, $byParent, $sortSiblings): Collection {
            return $sortSiblings($byParent->get($parentKey) ?? collect())
                ->map(fn (User $user) => [
                    'user' => $user,
                    'children' => $build((string) $user->id),
                ]);
        };

        return $build('root');
    }
}
