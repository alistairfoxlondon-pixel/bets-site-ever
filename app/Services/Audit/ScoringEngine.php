<?php

namespace App\Services\Audit;

/**
 * Turns findings into a readiness score, an approval probability and a
 * verdict. The weights live in config/adready.php so they can be tuned
 * without touching code.
 */
class ScoringEngine
{
    /**
     * @param  array<int,array<string,mixed>>  $findings
     * @return array{score:int,probability:int,verdict:string,category_scores:array<string,float>,breakdown:array<string,int>}
     */
    public function score(array $findings): array
    {
        $categories = config('adready.score.categories');
        $penalties = config('adready.score.penalties');

        $categoryScores = [];
        $breakdown = [];

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

            $weight = (float) $meta['weight'];
            $raw = $weight > 0 ? ($weight - $penalty) / $weight * 100 : 100;
            $score = max(0, min(100, $raw));

            $categoryScores[$key] = round($score, 1);
            $breakdown[$key] = count($categoryFindings);
        }

        $overall = array_sum($categoryScores) / max(1, count($categoryScores));
        $criticalCount = count(array_filter($findings, fn (array $f) => $f['severity'] === AuditFindingSeverity::CRITICAL));

        // Critical issues pull the probability down harder than the raw score.
        $probability = (int) round(max(0, min(100, $overall - ($criticalCount * 6))));

        $verdict = 'high_risk';

        foreach (config('adready.score.verdicts') as $key => $meta) {
            if ($probability >= $meta['min']) {
                $verdict = $key;

                break;
            }
        }

        return [
            'score' => (int) round($overall),
            'probability' => $probability,
            'verdict' => $verdict,
            'category_scores' => $categoryScores,
            'breakdown' => $breakdown,
        ];
    }
}
