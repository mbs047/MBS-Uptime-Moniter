<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'brand_name',
        'brand_tagline',
        'brand_url',
        'support_email',
        'mail_from_name',
        'mail_from_address',
        'seo_title',
        'seo_description',
        'uptime_window_days',
        'raw_run_retention_days',
        'default_failure_threshold',
        'default_recovery_threshold',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? new static([
            'brand_name' => 'Status Center',
            'brand_tagline' => 'Operational visibility for critical services',
            'uptime_window_days' => 90,
            'raw_run_retention_days' => 14,
            'default_failure_threshold' => 2,
            'default_recovery_threshold' => 1,
        ]);
    }
}
