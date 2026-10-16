<?php

namespace App\Services\Audit;

use App\Models\Audit;
use App\Models\AuditFinding;
use App\Models\Setting;
use App\Services\Ai\AiClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Runs a complete audit: crawl, deterministic checks, model review,
 * scoring and persistence.
 */
class AuditService
{
    public function __construct(
        private readonly SiteCrawler $crawler = new SiteCrawler,
        private readonly RuleEngine $rules = new RuleEngine,
        private readonly ScoringEngine $scoring = new ScoringEngine,
        private readonly AdNetworkAnalyzer $networks = new AdNetworkAnalyzer,
        private readonly PromptFactory $prompts = new PromptFactory,
    ) {
    }

    public function run(Audit $audit): Audit
    {
        $started = microtime(true);

        $audit->forceFill([
            'status' => Audit::STATUS_RUNNING,
            'started_at' => now(),
        ])->save();

        try {
            $mode = $this->modeConfig($audit->mode);
            $snapshot = $this->crawler->crawl($audit->normalized_url, (int) $mode['max_pages']);

            $findings = $this->rules->check($snapshot);
            $networkReadiness = $this->networks->analyze($snapshot);

            $aiResult = null;

            if ($mode['with_ai'] && AiClient::make()->hasKey()) {
                $aiResult = $this->askModel($snapshot, $audit, $mode, $networkReadiness);
            }

            $findings = array_merge($findings, $aiResult['findings'] ?? []);

            $scored = $this->scoring->score($findings);

            $readinessIndex = $scored['readiness_index'];
            $verdict = $this->verdictFor($readinessIndex);

            $audit->findings()->delete();

            foreach (array_values($findings) as $index => $finding) {
                AuditFinding::create([
                    'audit_id' => $audit->id,
                    'category' => $finding['category'],
                    'severity' => $finding['severity'],
                    'title' => mb_substr($finding['title'], 0, 250),
                    'detail' => $finding['detail'],
                    'recommendation' => $finding['recommendation'],
                    'evidence' => $finding['evidence'] ?? [],
                    'weight' => (int) ($finding['weight'] ?? 0),
                    'sort_order' => $index,
                ]);
            }

            $audit->forceFill([
                'status' => Audit::STATUS_COMPLETED,
                'score' => $scored['score'],
                'probability' => $readinessIndex,
                'verdict' => $verdict,
                'model' => $aiResult['model'] ?? null,
                'tokens_used' => (int) ($aiResult['tokens'] ?? 0),
                'cost' => (float) ($aiResult['cost'] ?? 0),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'pages_crawled' => count($snapshot->pages),
                'findings_count' => count($findings),
                'completed_at' => now(),
                'meta' => array_merge($audit->meta ?? [], [
                    'category_scores' => $scored['category_scores'],
                    'network_readiness' => $networkReadiness,
                    'analysis' => $aiResult['analysis'] ?? null,
                    'roadmap' => $aiResult['roadmap'] ?? [],
                    'strengths' => $aiResult['strengths'] ?? [],
                    'risks' => $aiResult['risks'] ?? [],
                    'critical_fixes' => $aiResult['critical_fixes'] ?? [],
                    'summary' => $aiResult['summary'] ?? null,
                    'ai_error' => $aiResult['error'] ?? null,
                ]),
            ])->save();

            return $audit->fresh();
        } catch (Throwable $exception) {
            Log::error('Audit failed', [
                'audit_id' => $audit->id,
                'url' => $audit->url,
                'message' => $exception->getMessage(),
            ]);

            $audit->forceFill([
                'status' => Audit::STATUS_FAILED,
                'error_message' => Str::limit($exception->getMessage(), 500),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'completed_at' => now(),
            ])->save();

            return $audit->fresh();
        }
    }

    /**
     * @return array{findings:array,model:?string,tokens:int,cost:float,analysis:array,roadmap:array,strengths:array,risks:array,critical_fixes:array,summary:?string,error:?string}
     */
    private function askModel(SiteSnapshot $snapshot, Audit $audit, array $mode, array $networkReadiness): array
    {
        $client = AiClient::make();
        $model = (string) (Setting::get('ai.model_'.$audit->mode) ?: $mode['model']);

        $response = $client->chatJson([
            ['role' => 'system', 'content' => $this->prompts->auditSystemPrompt()],
            ['role' => 'user', 'content' => $this->prompts->auditUserPrompt($snapshot, $audit->mode, $networkReadiness)],
        ], $model, [
            'temperature' => $mode['temperature'],
            'max_tokens' => $mode['max_tokens'],
            'response_format' => ['type' => 'json_object'],
        ]);

        if (! $response->successful) {
            return [
                'findings' => [],
                'model' => null,
                'tokens' => 0,
                'cost' => 0.0,
                'analysis' => [],
                'roadmap' => [],
                'strengths' => [],
                'risks' => [],
                'critical_fixes' => [],
                'summary' => null,
                'error' => $response->error,
            ];
        }

        $data = $response->data ?? [];
        $findings = [];

        foreach ((array) ($data['extra_findings'] ?? []) as $finding) {
            if (! is_array($finding) || blank($finding['title'] ?? null)) {
                continue;
            }

            $category = (string) ($finding['category'] ?? 'content_quality');

            if (! array_key_exists($category, config('adready.score.categories'))) {
                $category = 'content_quality';
            }

            $severity = (string) ($finding['severity'] ?? 'info');

            if (! in_array($severity, AuditFindingSeverity::all(), true)) {
                $severity = 'info';
            }

            // Model-generated findings have no independently verified evidence;
            // reserve critical severity for deterministic checks.
            if ($severity === AuditFindingSeverity::CRITICAL) {
                $severity = AuditFindingSeverity::WARNING;
            }

            $findings[] = [
                'category' => $category,
                'severity' => $severity,
                'title' => (string) $finding['title'],
                'detail' => (string) ($finding['detail'] ?? ''),
                'recommendation' => $finding['recommendation'] ?? null,
                'evidence' => [],
                'weight' => $severity === 'critical' ? 12 : ($severity === 'warning' ? 6 : 0),
            ];
        }

        $analysis = [
            'website_quality' => (string) ($data['website_quality'] ?? ''),
            'content_quality' => (string) ($data['content_quality'] ?? ''),
            'traffic_quality' => (string) ($data['traffic_quality'] ?? ''),
            'policy_risks' => (string) ($data['policy_risks'] ?? ''),
            'trust_eeat' => (string) ($data['trust_eeat'] ?? ''),
            'seo_indexing' => (string) ($data['seo_indexing'] ?? ''),
        ];

        $publicReadiness = (array) ($data['public_readiness'] ?? $data['approval_readiness'] ?? []);
        $summary = match (Str::upper(trim((string) ($data['verdict'] ?? '')))) {
            'STRONG PUBLIC SIGNALS' => 'Strong public signals',
            'IMPROVEMENTS RECOMMENDED' => 'Improvements recommended',
            'SIGNIFICANT GAPS' => 'Significant public gaps',
            default => null,
        };

        return [
            'findings' => $findings,
            'model' => $response->model,
            'tokens' => $response->totalTokens,
            'cost' => (float) Setting::get('ai.price_per_million', 0),
            'analysis' => $analysis,
            'roadmap' => array_values((array) ($data['roadmap'] ?? [])),
            'strengths' => array_values((array) ($publicReadiness['strengths'] ?? [])),
            'risks' => array_values((array) ($publicReadiness['risks'] ?? [])),
            'critical_fixes' => array_values((array) ($publicReadiness['priority_fixes'] ?? $publicReadiness['critical_fixes'] ?? [])),
            'summary' => $summary,
            'error' => null,
        ];
    }

    public function modeConfig(string $mode): array
    {
        $modes = config('adready.modes');
        $mode = array_key_exists($mode, $modes) ? $mode : 'standard';

        return $modes[$mode];
    }

    private function verdictFor(int $readinessIndex): string
    {
        foreach (config('adready.score.verdicts') as $key => $meta) {
            if ($readinessIndex >= $meta['min']) {
                return $key;
            }
        }

        return 'high_risk';
    }
}
