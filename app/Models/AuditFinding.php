<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditFinding extends Model
{
    use HasFactory;

    public const SEVERITY_CRITICAL = 'critical';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_PASS = 'pass';

    public const SEVERITY_INFO = 'info';

    protected $fillable = [
        'audit_id',
        'category',
        'severity',
        'title',
        'detail',
        'recommendation',
        'evidence',
        'weight',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'evidence' => 'array',
            'weight' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function severityLabel(): string
    {
        return match ($this->severity) {
            self::SEVERITY_CRITICAL => 'Must fix',
            self::SEVERITY_WARNING => 'Needs work',
            self::SEVERITY_PASS => 'Good',
            self::SEVERITY_INFO => 'Note',
            default => ucfirst($this->severity),
        };
    }

    public function categoryLabel(): string
    {
        return (string) (config('adready.score.categories.'.$this->category.'.label') ?: str_replace('_', ' ', $this->category));
    }
}
