<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'code',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->ordered();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public static function nextSortOrder(): int
    {
        return (int) static::max('sort_order') + 1;
    }

    public function displayName(): string
    {
        if ($this->relationLoaded('parent') && $this->parent) {
            return $this->parent->name.' › '.$this->name;
        }

        if ($this->parent_id) {
            return ($this->parent?->name ?? 'Parent').' › '.$this->name;
        }

        return $this->name;
    }

    public function descendantIds(): array
    {
        $ids = [];

        $walk = function (self $department) use (&$walk, &$ids): void {
            foreach ($department->children()->get(['id', 'parent_id']) as $child) {
                $ids[] = $child->id;
                $walk($child);
            }
        };

        $walk($this);

        return $ids;
    }

    public function wouldCreateCycle(?int $parentId): bool
    {
        if (! $parentId) {
            return false;
        }

        if ($this->id && (int) $parentId === (int) $this->id) {
            return true;
        }

        if ($this->id && in_array($parentId, $this->descendantIds(), true)) {
            return true;
        }

        return false;
    }

    public static function optionsForSelect(?self $exclude = null): Collection
    {
        $excludedIds = $exclude
            ? array_merge([$exclude->id], $exclude->descendantIds())
            : [];

        return static::query()
            ->with('parent')
            ->when($excludedIds, fn (Builder $q) => $q->whereNotIn('id', $excludedIds))
            ->ordered()
            ->get()
            ->map(fn (self $department) => (object) [
                'id' => $department->id,
                'name' => $department->displayName(),
            ]);
    }
}
