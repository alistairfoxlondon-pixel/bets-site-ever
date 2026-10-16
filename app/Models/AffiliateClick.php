<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateClick extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_code',
        'user_id',
        'ip_hash',
        'user_agent',
        'landing_path',
        'referrer',
        'country',
        'converted',
        'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'converted' => 'boolean',
            'converted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
