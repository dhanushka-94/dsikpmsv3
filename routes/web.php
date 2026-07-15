<?php

use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\ForcePasswordChangeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\PlantController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\KpiCategoryController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\ProjectCategoryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserTreeController;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware(['auth', EnsureUserIsActive::class])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/password/change', [ForcePasswordChangeController::class, 'edit'])->name('password.force.edit');
    Route::put('/password/change', [ForcePasswordChangeController::class, 'update'])->name('password.force.update');

    Route::middleware(EnsurePasswordIsChanged::class)->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/quick-access', [TaskController::class, 'quickAccess'])->name('quick-access');
        Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
        Route::get('/tasks/board', [TaskController::class, 'globalBoard'])->name('tasks.board');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');

        // Assigned users can view / feed their KPIs; create/edit/delete stay admin-only below.
        // Register index first; keep kpis/{kpi} AFTER create so "create" is not captured as an id.
        Route::get('kpis', [KpiController::class, 'index'])->name('kpis.index');

        Route::middleware('role:super_admin,admin')->group(function () {
            Route::resource('departments', DepartmentController::class)->except(['show']);
            Route::resource('designations', DesignationController::class)->except(['show']);
            Route::resource('companies', CompanyController::class)->except(['show']);
            Route::resource('plants', PlantController::class)->except(['show']);
            Route::resource('users', UserController::class);
            Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])
                ->name('users.reset-password');

            Route::get('/users-tree', UserTreeController::class)->name('users.tree');
            Route::get('/users-tree/{user}/projects', [UserTreeController::class, 'projects'])->name('users.tree.projects');
            Route::get('/users-tree/{user}/tasks', [UserTreeController::class, 'tasks'])->name('users.tree.tasks');
            Route::get('/people/{user}', [UserController::class, 'profile'])->name('users.profile');

            Route::resource('project-categories', ProjectCategoryController::class)->except(['show']);
            Route::resource('kpi-categories', KpiCategoryController::class)->except(['show']);
            Route::resource('kpis', KpiController::class)->except(['index', 'show']);
            Route::delete('kpis/{kpi}/results/{result}', [KpiController::class, 'destroyResult'])->name('kpis.results.destroy');

            Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
            Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
            Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

            Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
            Route::get('activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->name('activity-logs.show');
            Route::get('users/{user}/activity', [ActivityLogController::class, 'forUser'])->name('activity-logs.user');
        });

        Route::get('kpis/{kpi}', [KpiController::class, 'show'])->name('kpis.show');
        Route::post('kpis/{kpi}/calculate', [KpiController::class, 'calculate'])->name('kpis.calculate');

        Route::middleware('role:super_admin')->group(function () {
            Route::get('changelog', ChangelogController::class)->name('changelog.index');
        });

        Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
        Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::patch('/projects/{project}/users/{user}/toggle', [ProjectController::class, 'toggleUser'])->name('projects.users.toggle');
        Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

        Route::get('/projects/{project}/tasks', [TaskController::class, 'board'])->name('projects.tasks.board');
        Route::get('/projects/{project}/tasks/gantt', [TaskController::class, 'gantt'])->name('projects.tasks.gantt');
        Route::get('/projects/{project}/tasks/create', [TaskController::class, 'create'])->name('projects.tasks.create');

        Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
        Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
        Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
        Route::patch('/tasks/{task}/assignees/{user}/toggle', [TaskController::class, 'toggleAssignee'])->name('tasks.assignees.toggle');
    });
});
