<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'kpi_id',
        'recorded_on',
        'values',
        'result',
        'formula_snapshot',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'recorded_on' => 'date',
            'values' => 'array',
            'result' => 'decimal:4',
        ];
    }

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(Kpi::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
