<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'priority',
        'status',
        'description',
        'starts_at',
        'ends_at',
        'sort_order',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('is_enabled')
            ->withTimestamps();
    }

    public function enabledAssignees(): BelongsToMany
    {
        return $this->assignees()->wherePivot('is_enabled', true);
    }

    public function assigneeNames(): string
    {
        return $this->assignees
            ->filter(fn (User $user) => (bool) ($user->pivot->is_enabled ?? true))
            ->map(fn (User $user) => $user->displayName())
            ->join(', ');
    }
}
