<?php

namespace App\Models;

use App\Enums\ProjectPermission;
use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'year',
        'project_category_id',
        'department_id',
        'reference_number',
        'description',
        'start_date',
        'end_date',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => ProjectStatus::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectCategory::class, 'project_category_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('permission')
            ->withTimestamps();
    }

    public function permissionFor(?User $user): ?ProjectPermission
    {
        if (! $user) {
            return null;
        }

        $assignment = $this->users->firstWhere('id', $user->id);

        if (! $assignment) {
            return null;
        }

        return ProjectPermission::from($assignment->pivot->permission);
    }

    public function isAssignedTo(User $user): bool
    {
        if ($this->relationLoaded('users')) {
            return $this->users->contains('id', $user->id);
        }

        return $this->users()->where('users.id', $user->id)->exists();
    }

    public function canBeEditedBy(User $user): bool
    {
        if ($user->canManageUsers()) {
            return true;
        }

        return $this->permissionFor($user) === ProjectPermission::Editor;
    }

    public function canBeViewedBy(User $user): bool
    {
        if ($user->canManageUsers()) {
            return true;
        }

        return $this->isAssignedTo($user);
    }

    public static function yearOptions(?int $center = null): array
    {
        $center ??= (int) now('Asia/Colombo')->year;
        $years = [];

        for ($year = $center - 5; $year <= $center + 5; $year++) {
            $label = (string) $year;
            if ($year === $center) {
                $label .= ' (Current)';
            } elseif ($year === $center + 1) {
                $label .= ' (Next)';
            } elseif ($year === $center - 1) {
                $label .= ' (Previous)';
            }
            $years[$year] = $label;
        }

        return $years;
    }
}
