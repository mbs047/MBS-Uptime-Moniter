<?php

namespace App\Models;

use App\Enums\RemoteIntegrationAuthMode;
use App\Enums\RemoteIntegrationSyncMode;
use App\Enums\RemoteIntegrationSyncStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RemoteIntegration extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'remote_app_id',
        'base_url',
        'health_url',
        'metadata_url',
        'sync_mode',
        'auth_mode',
        'auth_secret',
        'service_id',
        'last_sync_status',
        'last_sync_error',
        'last_synced_at',
        'last_registration_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sync_mode' => RemoteIntegrationSyncMode::class,
            'auth_mode' => RemoteIntegrationAuthMode::class,
            'auth_secret' => 'encrypted',
            'last_sync_status' => RemoteIntegrationSyncStatus::class,
            'last_synced_at' => 'datetime',
            'last_registration_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(Component::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(Check::class);
    }
}
