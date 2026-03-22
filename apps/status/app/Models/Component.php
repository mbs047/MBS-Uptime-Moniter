<?php

namespace App\Models;

use App\Enums\ComponentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Component extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'service_id',
        'remote_integration_id',
        'remote_component_key',
        'display_name',
        'description',
        'sort_order',
        'status',
        'automated_status',
        'is_public',
        'last_status_changed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ComponentStatus::class,
            'automated_status' => ComponentStatus::class,
            'is_public' => 'boolean',
            'last_status_changed_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function remoteIntegration(): BelongsTo
    {
        return $this->belongsTo(RemoteIntegration::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(Check::class)->orderBy('name');
    }

    public function dailyUptimes(): HasMany
    {
        return $this->hasMany(ComponentDailyUptime::class)->orderByDesc('day');
    }

    public function incidents(): BelongsToMany
    {
        return $this->belongsToMany(Incident::class, 'component_incident');
    }
}
