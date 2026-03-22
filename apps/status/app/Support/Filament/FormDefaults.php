<?php

namespace App\Support\Filament;

use App\Enums\CheckType;
use App\Enums\IncidentSeverity;
use App\Models\PlatformSetting;

class FormDefaults
{
    /**
     * @return array<string, mixed>
     */
    public static function platformSettings(): array
    {
        $settings = PlatformSetting::current();
        $brandName = $settings->brand_name ?: 'Status Center';
        $brandTagline = $settings->brand_tagline ?: 'Operational visibility for critical services';
        $appUrl = filled(config('app.url')) ? rtrim((string) config('app.url'), '/') : null;
        $mailFromAddress = filled(config('mail.from.address')) ? trim((string) config('mail.from.address')) : null;
        $mailFromName = filled(config('mail.from.name')) ? trim((string) config('mail.from.name')) : $brandName;

        return [
            'brand_name' => $brandName,
            'brand_tagline' => $brandTagline,
            'brand_url' => $settings->brand_url ?: $appUrl,
            'support_email' => $settings->support_email ?: $mailFromAddress,
            'mail_from_name' => $settings->mail_from_name ?: $mailFromName,
            'mail_from_address' => $settings->mail_from_address ?: $mailFromAddress,
            'probe_registration_token' => $settings->probe_registration_token,
            'seo_title' => $settings->seo_title ?: $brandName,
            'seo_description' => $settings->seo_description ?: $brandTagline,
            'uptime_window_days' => $settings->uptime_window_days ?: 90,
            'raw_run_retention_days' => $settings->raw_run_retention_days ?: 14,
            'default_failure_threshold' => $settings->default_failure_threshold ?: 2,
            'default_recovery_threshold' => $settings->default_recovery_threshold ?: 1,
        ];
    }

    public static function platformSetting(string $key): mixed
    {
        return static::platformSettings()[$key] ?? null;
    }

    public static function checkType(): string
    {
        return CheckType::Http->value;
    }

    /**
     * @return list<int>
     */
    public static function httpExpectedStatuses(): array
    {
        return [200];
    }

    public static function httpsPort(): int
    {
        return 443;
    }

    public static function incidentSeverity(): string
    {
        return IncidentSeverity::Degraded->value;
    }
}
