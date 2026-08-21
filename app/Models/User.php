<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserTitle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'title',
        'calling_name',
        'middle_initials',
        'last_name',
        'name',
        'email',
        'epf_number',
        'joined_date',
        'company_id',
        'plant_id',
        'department_id',
        'designation_id',
        'role',
        'parent_user_id',
        'profile_picture',
        'is_active',
        'must_change_password',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'joined_date' => 'date',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'role' => UserRole::class,
            'title' => UserTitle::class,
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(User::class, 'parent_user_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->withPivot(['permission', 'is_enabled'])
            ->withTimestamps();
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class)
            ->withPivot('is_enabled')
            ->withTimestamps();
    }

    public function kpis(): BelongsToMany
    {
        return $this->belongsToMany(Kpi::class)
            ->withPivot('weightage')
            ->withTimestamps();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function canManageUsers(): bool
    {
        return in_array($this->role, [UserRole::SuperAdmin, UserRole::Admin], true);
    }

    public function canResetPasswords(): bool
    {
        return in_array($this->role, [UserRole::SuperAdmin, UserRole::Admin], true);
    }

    public function displayName(): string
    {
        $parts = array_filter([
            $this->calling_name,
            $this->middle_initials,
            $this->last_name,
        ], fn ($part) => filled($part));

        $fullName = $parts !== [] ? implode(' ', $parts) : $this->name;

        return trim(($this->title?->value ? $this->title->value.' ' : '').$fullName);
    }

    public static function composeFullName(?string $callingName, ?string $middleInitials, ?string $lastName): string
    {
        return trim(implode(' ', array_filter([
            $callingName,
            $middleInitials,
            $lastName,
        ], fn ($part) => filled($part))));
    }

    public function profilePictureUrl(): ?string
    {
        if (! $this->profile_picture) {
            return null;
        }

        return asset('storage/'.$this->profile_picture);
    }
}
