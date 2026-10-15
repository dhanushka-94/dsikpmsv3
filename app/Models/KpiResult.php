<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class KpiResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'kpi_id',
        'recorded_on',
        'values',
        'result',
        'formula_snapshot',
        'attachments',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'recorded_on' => 'date',
            'values' => 'array',
            'result' => 'decimal:4',
            'attachments' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (KpiResult $result) {
            $result->deleteStoredAttachments();
        });
    }

    public function deleteStoredAttachments(): void
    {
        $directory = 'kpi-results/'.$this->kpi_id.'/'.$this->id;

        if (Storage::disk('local')->directoryExists($directory)) {
            Storage::disk('local')->deleteDirectory($directory);
        }
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
