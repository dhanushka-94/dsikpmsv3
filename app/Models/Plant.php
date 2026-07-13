<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Plant extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public static function nextSortOrder(?int $companyId = null): int
    {
        return (int) static::query()
            ->when($companyId, fn (Builder $q) => $q->where('company_id', $companyId))
            ->max('sort_order') + 1;
    }

    public function displayName(): string
    {
        if ($this->relationLoaded('company') && $this->company) {
            return $this->company->name.' › '.$this->name;
        }

        return $this->name;
    }

    public static function assertBelongsToCompany(?int $plantId, ?int $companyId): void
    {
        if (! $plantId || ! $companyId) {
            return;
        }

        $matches = static::query()
            ->whereKey($plantId)
            ->where('company_id', $companyId)
            ->exists();

        if (! $matches) {
            throw ValidationException::withMessages([
                'plant_id' => 'Selected plant does not belong to the selected company.',
            ]);
        }
    }
}
