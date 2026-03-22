<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComponentDailyUptime extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'component_id',
        'day',
        'healthy_slots',
        'observed_slots',
        'maintenance_slots',
        'no_data_slots',
        'uptime_percentage',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day' => 'date',
            'uptime_percentage' => 'decimal:2',
        ];
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }
}
