<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Audit extends Model
{
    use HasFactory;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'url',
        'normalized_url',
        'domain',
        'mode',
        'status',
        'score',
        'probability',
        'verdict',
        'model',
        'tokens_used',
        'cost',
        'duration_ms',
        'pages_crawled',
        'findings_count',
        'error_message',
        'ip',
        'meta',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'cost' => 'decimal:6',
            'tokens_used' => 'integer',
            'duration_ms' => 'integer',
            'pages_crawled' => 'integer',
            'findings_count' => 'integer',
            'score' => 'integer',
            'probability' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(AuditFinding::class)->orderBy('sort_order');
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }

    public function verdictLabel(): string
    {
        $verdicts = config('adready.score.verdicts');
        $key = $this->verdict ?? 'high_risk';

        return $verdicts[$key]['label'] ?? ucfirst(str_replace('_', ' ', $key));
    }

    public function severityCounts(): array
    {
        $counts = ['critical' => 0, 'warning' => 0, 'pass' => 0, 'info' => 0];

        foreach ($this->findings as $finding) {
            $counts[$finding->severity] = ($counts[$finding->severity] ?? 0) + 1;
        }

        return $counts;
    }

    public function categoryScores(): array
    {
        $out = [];

        foreach (config('adready.score.categories') as $key => $meta) {
            $out[$key] = [
                'label' => $meta['label'],
                'weight' => $meta['weight'],
                'score' => (float) (data_get($this->meta, "category_scores.$key", 100)),
                'findings' => $this->findings->where('category', $key)->count(),
            ];
        }

        return $out;
    }
}
