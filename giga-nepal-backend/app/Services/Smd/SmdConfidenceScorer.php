<?php

namespace App\Services\Smd;

/**
 * Confidence scoring for SMD marking matches.
 *
 * Never labels a result "confirmed" from the marking database alone —
 * manufacturer or datasheet evidence OR explicit human verification required.
 */
class SmdConfidenceScorer
{
    public const STRONGLY_VERIFIED = 90;
    public const HIGH_CONFIDENCE = 75;
    public const POSSIBLE = 50;
    public const WEAK = 0;

    /**
     * Score a candidate match and return {score, factors, class}.
     */
    public function score(array $query, array $candidate, bool $hasVerifiedProduct, bool $hasDatasheetEvidence): array
    {
        $factors = [];
        $total = 0;

        // Exact marking match
        if ($query['marking_matches_exact'] ?? false) {
            $factors['exact_marking'] = 30;
            $total += 30;
        }

        // Package match
        if ($query['package_matches'] ?? false) {
            $factors['package_match'] = 25;
            $total += 25;
        } elseif ($query['package_conflict'] ?? false) {
            $factors['package_mismatch'] = -40;
            $total -= 40;
        }

        // Manufacturer match
        if ($query['manufacturer_matches'] ?? false) {
            $factors['manufacturer_match'] = 20;
            $total += 20;
        } elseif ($query['manufacturer_conflict'] ?? false) {
            $factors['manufacturer_mismatch'] = -30;
            $total -= 30;
        }

        // Pin count match
        if ($query['pin_count_matches'] ?? false) {
            $factors['pin_count_match'] = 10;
            $total += 10;
        }

        // Component function match (e.g., user searched "voltage regulator", candidate IS one)
        if ($query['function_matches'] ?? false) {
            $factors['function_match'] = 10;
            $total += 10;
        }

        // Electrical context match (voltage/current within expected range)
        if ($query['electrical_context_match'] ?? false) {
            $factors['electrical_match'] = 10;
            $total += 10;
        }

        // Boost for existing verified NeoGiga product
        if ($hasVerifiedProduct) {
            $factors['verified_product'] = 10;
            $total += 10;
        }

        // Major boost for manufacturer datasheet confirmation
        if ($hasDatasheetEvidence) {
            $factors['datasheet_confirms'] = 30;
            $total += 30;
        }

        // Function mismatch penalty
        if ($query['function_conflict'] ?? false) {
            $factors['function_mismatch'] = -30;
            $total -= 30;
        }

        $total = max(0, min(100, $total));

        return [
            'score' => $total,
            'factors' => $factors,
            'class' => $this->classify($total),
        ];
    }

    public function classify(int $score): string
    {
        return match (true) {
            $score >= self::STRONGLY_VERIFIED => 'strongly_verified',
            $score >= self::HIGH_CONFIDENCE => 'high_confidence',
            $score >= self::POSSIBLE => 'possible',
            default => 'weak',
        };
    }

    public function classLabel(string $class): string
    {
        return match ($class) {
            'strongly_verified' => 'Strongly Verified',
            'high_confidence' => 'High Confidence',
            'possible' => 'Possible Candidate',
            'weak' => 'Weak Candidate',
            default => 'Unknown',
        };
    }
}
