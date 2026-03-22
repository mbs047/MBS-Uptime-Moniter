<?php

namespace App\Models;

use App\Enums\ComponentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
        'status',
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
            'is_public' => 'boolean',
            'last_status_changed_at' => 'datetime',
        ];
    }

    public function components(): HasMany
    {
        return $this->hasMany(Component::class)->orderBy('sort_order');
    }

    public function incidents(): BelongsToMany
    {
        return $this->belongsToMany(Incident::class, 'incident_service');
    }
}
