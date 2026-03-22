<?php

namespace App\Models;

use Database\Factories\AdminFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class Admin extends Authenticatable implements CanResetPasswordContract, FilamentUser
{
    use CanResetPassword;

    /** @use HasFactory<AdminFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'is_active',
        'last_login_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $admin): void {
            if (! $admin->exists || ! $admin->isDirty('is_active') || $admin->is_active) {
                return;
            }

            if ($admin->isCurrentAdmin()) {
                throw ValidationException::withMessages([
                    'is_active' => 'You cannot disable the admin account that is currently signed in.',
                ]);
            }

            if ($admin->isLastActiveAdmin()) {
                throw ValidationException::withMessages([
                    'is_active' => 'At least one active admin account must remain available.',
                ]);
            }
        });

        static::deleting(function (self $admin): void {
            if ($admin->isCurrentAdmin()) {
                throw ValidationException::withMessages([
                    'admin' => 'You cannot delete the admin account that is currently signed in.',
                ]);
            }

            if ($admin->isLastActiveAdmin()) {
                throw ValidationException::withMessages([
                    'admin' => 'At least one active admin account must remain available.',
                ]);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function invites(): HasMany
    {
        return $this->hasMany(AdminInvite::class, 'created_by_admin_id');
    }

    public function incidentUpdates(): HasMany
    {
        return $this->hasMany(IncidentUpdate::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function isCurrentAdmin(): bool
    {
        return filled($this->getKey()) && Auth::guard('admin')->id() === $this->getKey();
    }

    public function isLastActiveAdmin(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return static::query()
            ->whereKeyNot($this->getKey())
            ->where('is_active', true)
            ->doesntExist();
    }
}
