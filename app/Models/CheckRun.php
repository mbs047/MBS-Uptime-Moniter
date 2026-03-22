<?php

namespace App\Models;

use App\Enums\CheckRunOutcome;
use App\Enums\ComponentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckRun extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'check_id',
        'outcome',
        'severity',
        'status_code',
        'latency_ms',
        'result_payload',
        'error_payload',
        'started_at',
        'finished_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'outcome' => CheckRunOutcome::class,
            'severity' => ComponentStatus::class,
            'result_payload' => 'array',
            'error_payload' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function check(): BelongsTo
    {
        return $this->belongsTo(Check::class);
    }
}
