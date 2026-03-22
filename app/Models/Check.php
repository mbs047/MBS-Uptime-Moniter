<?php

namespace App\Models;

use App\Enums\CheckType;
use App\Enums\ComponentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Check extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'component_id',
        'name',
        'type',
        'interval_minutes',
        'timeout_seconds',
        'failure_threshold',
        'recovery_threshold',
        'enabled',
        'config',
        'secret_config',
        'next_run_at',
        'last_ran_at',
        'consecutive_failures',
        'consecutive_recoveries',
        'latest_severity',
        'latest_error_summary',
        'latest_latency_ms',
        'latest_http_status',
        'latest_succeeded_at',
        'latest_failed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CheckType::class,
            'enabled' => 'boolean',
            'config' => 'array',
            'secret_config' => 'encrypted:array',
            'next_run_at' => 'datetime',
            'last_ran_at' => 'datetime',
            'latest_succeeded_at' => 'datetime',
            'latest_failed_at' => 'datetime',
            'latest_severity' => ComponentStatus::class,
        ];
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(CheckRun::class)->orderByDesc('started_at');
    }
}
