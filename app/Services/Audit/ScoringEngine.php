<?php

namespace App\Services\Audit;

/**
 * Converts observed findings into a product-defined public-site readiness
 * index. It is not an approval probability or a network eligibility score.
 */
class ScoringEngine
{
    /**
     * @param  array<int,array<string,mixed>>  $findings
     * @return array{score:int,readiness_index:int,verdict:string,category_scores:array<string,float|null>,breakdown:array<string,int>}
     */
    public function score(array $findings): array
    {
        $categories = config('adready.score.categories');
        $penalties = config('adready.score.penalties');

        $categoryScores = [];
        $breakdown = [];
        $weightedScore = 0.0;
        $totalWeight = 0.0;

        foreach ($categories as $key => $meta) {
            $categoryFindings = array_values(array_filter($findings, fn (array $finding) => $finding['category'] === $key));

            $penalty = 0.0;
            $passes = 0;

            foreach ($categoryFindings as $finding) {
                $severity = $finding['severity'];

                if ($severity === AuditFindingSeverity::PASS) {
                    $passes++;

                    continue;
                }

                $weight = (int) ($finding['weight'] ?? 0);

                if ($weight > 0) {
                    $penalty += $weight;

                    continue;
                }

                $penalty += (float) ($penalties[$severity] ?? 0);
            }

            $importance = max(0.0, (float) ($meta['weight'] ?? 0));
            $breakdown[$key] = count($categoryFindings);

            if ($importance <= 0) {
                $categoryScores[$key] = null;

                continue;
            }

            $score = max(0.0, min(100.0, 100.0 - $penalty));
            $categoryScores[$key] = round($score, 1);
            $weightedScore += $score * $importance;
            $totalWeight += $importance;
        }

        $overall = $totalWeight > 0 ? $weightedScore / $totalWeight : 0;

        // This is a heuristic readiness index, not a statistically calibrated
        // approval probability. Critical findings already carry larger weights.
        $readinessIndex = (int) round(max(0, min(100, $overall)));

        $verdict = 'high_risk';

        foreach (config('adready.score.verdicts') as $key => $meta) {
            if ($readinessIndex >= $meta['min']) {
                $verdict = $key;

                break;
            }
        }

        return [
            'score' => (int) round($overall),
            'readiness_index' => $readinessIndex,
            'verdict' => $verdict,
            'category_scores' => $categoryScores,
            'breakdown' => $breakdown,
        ];
    }
}
