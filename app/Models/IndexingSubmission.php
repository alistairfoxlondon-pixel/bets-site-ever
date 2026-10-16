<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndexingSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'url',
        'engine',
        'status',
        'message',
        'response',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'response' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
