<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'verification_token',
        'unsubscribe_token',
        'verified_at',
        'unsubscribed_at',
        'last_confirmation_sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'last_confirmation_sent_at' => 'datetime',
        ];
    }

    public function scopeActiveRecipients(Builder $query): Builder
    {
        return $query
            ->whereNotNull('verified_at')
            ->whereNull('unsubscribed_at');
    }
}
