<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\ForcePasswordChangeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectCategoryController;
use App\Http\Controllers\ProjectController;
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

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('/users-tree', UserTreeController::class)->name('users.tree');

        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');

        Route::middleware('role:super_admin,admin')->group(function () {
            Route::resource('departments', DepartmentController::class)->except(['show']);
            Route::resource('designations', DesignationController::class)->except(['show']);
            Route::resource('users', UserController::class);
            Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])
                ->name('users.reset-password');

            Route::resource('project-categories', ProjectCategoryController::class)->except(['show']);

            Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
            Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
            Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

            Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
            Route::get('activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->name('activity-logs.show');
            Route::get('users/{user}/activity', [ActivityLogController::class, 'forUser'])->name('activity-logs.user');
        });

        Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
        Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    });
});
