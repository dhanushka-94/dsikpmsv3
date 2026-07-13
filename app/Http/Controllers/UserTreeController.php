<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class UserTreeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $departmentId = $request->integer('department_id') ?: null;
        $designationId = $request->integer('designation_id') ?: null;

        $users = User::query()
            ->with(['department.parent', 'designation', 'parent'])
            ->withCount('projects')
            ->where('role', '!=', 'super_admin')
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($designationId, fn ($q) => $q->where('designation_id', $designationId))
            ->get();

        $visibleIds = $users->pluck('id')->map(fn ($id) => (int) $id)->all();
        $roots = $this->buildTree($users, $visibleIds);

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
