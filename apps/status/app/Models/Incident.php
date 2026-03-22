<?php

namespace App\Models;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Incident extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'summary',
        'status',
        'severity',
        'starts_at',
        'scheduled_starts_at',
        'scheduled_ends_at',
        'resolved_at',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => IncidentStatus::class,
            'severity' => IncidentSeverity::class,
            'starts_at' => 'datetime',
            'scheduled_starts_at' => 'datetime',
            'scheduled_ends_at' => 'datetime',
            'resolved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'incident_service');
    }

    public function components(): BelongsToMany
    {
        return $this->belongsToMany(Component::class, 'component_incident');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(IncidentUpdate::class)->orderBy('created_at');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', IncidentStatus::Published)
            ->whereNotNull('published_at');
    }

    public function scopeActiveAt(Builder $query, Carbon $moment): Builder
    {
        return $query
            ->published()
            ->where(function (Builder $query) use ($moment): void {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $moment);
            })
            ->where(function (Builder $query) use ($moment): void {
                $query
                    ->whereNull('scheduled_starts_at')
                    ->orWhere('scheduled_starts_at', '<=', $moment);
            })
            ->where(function (Builder $query) use ($moment): void {
                $query
                    ->whereNull('resolved_at')
                    ->orWhere('resolved_at', '>', $moment);
            });
    }
}
