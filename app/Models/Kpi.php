<?php

namespace App\Models;

use App\Enums\BenchmarkType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\FormulaEvaluator;

class Kpi extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'kpi_index',
        'kpi_category_id',
        'definition',
        'formula',
        'formula_fields',
        'formula_values',
        'formula_result',
        'benchmark_percent',
        'benchmark_type',
        'start_date',
        'end_date',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'formula_fields' => 'array',
            'formula_values' => 'array',
            'formula_result' => 'decimal:4',
            'benchmark_percent' => 'decimal:2',
            'benchmark_type' => BenchmarkType::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KpiCategory::class, 'kpi_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('weightage')
            ->withTimestamps();
    }

    public function results(): HasMany
    {
        // Newest saves first so freshly fed data is visible at the top of history.
        return $this->hasMany(KpiResult::class)->orderByDesc('id');
    }

    public function isAssignedTo(User $user): bool
    {
        if ($this->relationLoaded('users')) {
            return $this->users->contains('id', $user->id);
        }

        return $this->users()->where('users.id', $user->id)->exists();
    }

    public function canBeViewedBy(User $user): bool
    {
        if ($user->canManageUsers()) {
            return true;
        }

        return $this->isAssignedTo($user);
    }

    public function canBeManagedBy(User $user): bool
    {
        return $user->canManageUsers();
    }

    /**
     * @return list<array{name: string}>
     */
    public function formulaFieldDefinitions(): array
    {
        return collect($this->formula_fields ?? [])
            ->map(function ($field) {
                $name = is_array($field) ? trim((string) ($field['name'] ?? '')) : trim((string) $field);

                return $name !== '' ? ['name' => $name] : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function formulaVariables(): array
    {
        $fromFields = collect($this->formulaFieldDefinitions())->pluck('name')->all();
        $fromFormula = FormulaEvaluator::variables($this->formula ?? '');

        return collect($fromFields ?: $fromFormula)->unique()->values()->all();
    }
}
