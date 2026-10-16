<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'billing_cycle',
        'gateway',
        'gateway_subscription_id',
        'amount',
        'currency',
        'amount_bdt',
        'trial_ends_at',
        'starts_at',
        'renews_at',
        'ends_at',
        'cancelled_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'amount' => 'decimal:2',
            'amount_bdt' => 'decimal:2',
            'trial_ends_at' => 'datetime',
            'starts_at' => 'datetime',
            'renews_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing'], true);
    }

    public function isOnTrial(): bool
    {
        return $this->status === 'trialing';
    }

    public function daysUntilRenewal(): ?int
    {
        if (! $this->renews_at) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->renews_at->startOfDay(), false);
    }
}
