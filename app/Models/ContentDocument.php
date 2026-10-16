<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'mode',
        'title',
        'slug',
        'keyword',
        'intent',
        'word_count',
        'body',
        'excerpt',
        'meta_title',
        'meta_description',
        'faq',
        'outline',
        'images',
        'internal_links',
        'external_links',
        'model',
        'tokens_used',
        'cost',
        'status',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'faq' => 'array',
            'outline' => 'array',
            'images' => 'array',
            'internal_links' => 'array',
            'external_links' => 'array',
            'meta' => 'array',
            'word_count' => 'integer',
            'tokens_used' => 'integer',
            'cost' => 'decimal:6',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAffiliate(): bool
    {
        return $this->type === 'affiliate_article';
    }

    public function readingMinutes(): int
    {
        return max(1, (int) ceil(((int) $this->word_count) / 220));
    }

    public function getReadingMinutesAttribute(): int
    {
        return $this->readingMinutes();
    }
}
