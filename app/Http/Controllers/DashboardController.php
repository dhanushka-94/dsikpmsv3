<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user()->load(['department', 'designation', 'parent']);
        $isAdmin = $user->canManageUsers();

        $myProjects = $user->projects()
            ->wherePivot('is_enabled', true)
            ->with(['category', 'department'])
            ->withCount('tasks')
            ->orderByDesc('updated_at')
            ->get();

        $myTasks = $user->tasks()
            ->wherePivot('is_enabled', true)
            ->with(['project'])
            ->orderBy('ends_at')
            ->get();

        $projectStats = [
            'total' => $myProjects->count(),
            'ongoing' => $myProjects->filter(fn ($project) => $project->status === ProjectStatus::Ongoing)->count(),
            'on_hold' => $myProjects->filter(fn ($project) => $project->status === ProjectStatus::OnHold)->count(),
            'completed' => $myProjects->filter(fn ($project) => $project->status === ProjectStatus::Completed)->count(),
        ];
        $projectStats['percent'] = $projectStats['total'] > 0
            ? (int) round(($projectStats['completed'] / $projectStats['total']) * 100)
            : 0;

        $taskStats = [
            'total' => $myTasks->count(),
            'todo' => $myTasks->filter(fn ($task) => $task->status === TaskStatus::Todo)->count(),
            'in_progress' => $myTasks->filter(fn ($task) => $task->status === TaskStatus::InProgress)->count(),
            'review' => $myTasks->filter(fn ($task) => $task->status === TaskStatus::Review)->count(),
            'done' => $myTasks->filter(fn ($task) => $task->status === TaskStatus::Done)->count(),
        ];
        $taskStats['percent'] = $taskStats['total'] > 0
            ? (int) round(($taskStats['done'] / $taskStats['total']) * 100)
            : 0;

        $upcomingTasks = $myTasks
            ->filter(fn ($task) => $task->status !== TaskStatus::Done)
            ->sortBy('ends_at')
            ->take(6)
            ->values();

        $recentProjects = $myProjects->take(5)->values();

        $systemStats = null;
        $recentActivity = collect();

        if ($isAdmin) {
            $systemStats = [
                'users' => User::query()->where('role', '!=', 'super_admin')->count(),
                'projects' => Project::query()->count(),
                'tasks' => Task::query()->count(),
                'ongoing_projects' => Project::query()->where('status', ProjectStatus::Ongoing)->count(),
                'open_tasks' => Task::query()->where('status', '!=', TaskStatus::Done)->count(),
            ];

            $recentActivity = ActivityLog::query()
                ->with('user')
                ->latest()
                ->limit(8)
                ->get();
        }

        return view('dashboard', [
            'user' => $user,
            'isAdmin' => $isAdmin,
            'projectStats' => $projectStats,
            'taskStats' => $taskStats,
            'upcomingTasks' => $upcomingTasks,
            'recentProjects' => $recentProjects,
            'systemStats' => $systemStats,
            'recentActivity' => $recentActivity,
        ]);
    }
}
