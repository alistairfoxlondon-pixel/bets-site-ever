<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'tagline',
        'description',
        'features',
        'price_monthly',
        'price_yearly',
        'price_monthly_bdt',
        'price_yearly_bdt',
        'credits',
        'max_sites',
        'trial_days',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'price_monthly_bdt' => 'decimal:2',
            'price_yearly_bdt' => 'decimal:2',
            'credits' => 'integer',
            'max_sites' => 'integer',
            'trial_days' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function priceFor(string $cycle, string $currency = 'USD'): float
    {
        $bdt = strtoupper($currency) === 'BDT';

        if ($cycle === 'yearly') {
            return (float) ($bdt ? $this->price_yearly_bdt : $this->price_yearly);
        }

        return (float) ($bdt ? $this->price_monthly_bdt : $this->price_monthly);
    }

    public function monthlyEquivalent(string $currency = 'USD'): float
    {
        $bdt = strtoupper($currency) === 'BDT';
        $yearly = (float) ($bdt ? $this->price_yearly_bdt : $this->price_yearly);

        return $yearly > 0 ? round($yearly / 12, 2) : $this->priceFor('monthly', $currency);
    }

    public function savingsPercent(string $currency = 'USD'): int
    {
        $monthly = $this->priceFor('monthly', $currency);
        $yearly = $this->priceFor('yearly', $currency);

        if ($monthly <= 0 || $yearly <= 0) {
            return 0;
        }

        return (int) round((1 - ($yearly / ($monthly * 12))) * 100);
    }
}
