<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\ActivityLog;
use App\Models\Kpi;
use App\Models\KpiCategory;
use App\Models\KpiResult;
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

        $myKpis = $user->kpis()
            ->with(['category'])
            ->withCount('results')
            ->orderByPivot('updated_at', 'desc')
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

        $kpiStats = [
            'assigned' => $myKpis->count(),
            'weightage_used' => round((float) $myKpis->sum(fn ($kpi) => (float) $kpi->pivot->weightage), 2),
            'with_results' => $myKpis->filter(fn ($kpi) => $kpi->results_count > 0)->count(),
        ];
        $kpiStats['weightage_remaining'] = round(max(0, 100 - $kpiStats['weightage_used']), 2);

        $upcomingTasks = $myTasks
            ->filter(fn ($task) => $task->status !== TaskStatus::Done)
            ->sortBy('ends_at')
            ->take(6)
            ->values();

        $recentProjects = $myProjects->take(5)->values();
        $myRecentKpis = $myKpis->take(5)->values();

        $systemStats = null;
        $recentActivity = collect();
        $recentKpis = collect();
        $recentKpiResults = collect();
        $kpiOverview = null;

        if ($isAdmin) {
            $activeKpis = Kpi::query()->where('is_active', true)->count();
            $resultsThisMonth = KpiResult::query()
                ->whereYear('recorded_on', now()->year)
                ->whereMonth('recorded_on', now()->month)
                ->count();

            $systemStats = [
                'users' => User::query()->where('role', '!=', 'super_admin')->count(),
                'projects' => Project::query()->count(),
                'tasks' => Task::query()->count(),
                'ongoing_projects' => Project::query()->where('status', ProjectStatus::Ongoing)->count(),
                'open_tasks' => Task::query()->where('status', '!=', TaskStatus::Done)->count(),
                'kpis' => Kpi::query()->count(),
                'active_kpis' => $activeKpis,
                'kpi_categories' => KpiCategory::query()->count(),
                'kpi_results' => KpiResult::query()->count(),
                'kpi_results_month' => $resultsThisMonth,
            ];

            $kpiOverview = [
                'total' => $systemStats['kpis'],
                'active' => $activeKpis,
                'categories' => $systemStats['kpi_categories'],
                'results' => $systemStats['kpi_results'],
                'results_month' => $resultsThisMonth,
                'avg_benchmark' => round((float) (Kpi::query()->avg('benchmark_percent') ?? 0), 2),
            ];

            $recentKpis = Kpi::query()
                ->with(['category'])
                ->withCount('results')
                ->latest()
                ->limit(5)
                ->get();

            $recentKpiResults = KpiResult::query()
                ->with(['kpi.category', 'creator'])
                ->latest()
                ->limit(6)
                ->get();

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
            'kpiStats' => $kpiStats,
            'upcomingTasks' => $upcomingTasks,
            'recentProjects' => $recentProjects,
            'myRecentKpis' => $myRecentKpis,
            'systemStats' => $systemStats,
            'kpiOverview' => $kpiOverview,
            'recentKpis' => $recentKpis,
            'recentKpiResults' => $recentKpiResults,
            'recentActivity' => $recentActivity,
        ]);
    }
}
