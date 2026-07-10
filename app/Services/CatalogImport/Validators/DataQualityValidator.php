<?php

namespace NeoGiga\CatalogImport\Services\Validators;

class DataQualityValidator
{
    // Scoring weights (must sum to 100)
    protected array $weights = [
        'manufacturer_matched' => 15,
        'mpn_valid' => 15,
        'category_matched' => 10,
        'name_present' => 10,
        'description_present' => 5,
        'datasheet_present' => 10,
        'required_specs_complete' => 20,
        'image_present' => 5,
        'lifecycle_known' => 5,
        'compliance_known' => 5
    ];

    /**
     * Calculate overall data quality score (0-100)
     */
    public function calculateScore(array $criteria): int
    {
        $score = 0;

        // Manufacturer matched (15 points)
        if (!empty($criteria['manufacturer_matched'])) {
            $score += $this->weights['manufacturer_matched'];
        }

        // MPN valid (15 points)
        if (!empty($criteria['mpn_present'])) {
            $score += $this->weights['mpn_valid'];
        }

        // Category matched (10 points)
        if (!empty($criteria['category_matched'])) {
            $score += $this->weights['category_matched'];
        }

        // Name present (10 points)
        if (!empty($criteria['name_present'])) {
            $score += $this->weights['name_present'];
        }

        // Description present (5 points)
        if (!empty($criteria['description_present'])) {
            $score += $this->weights['description_present'];
        }

        // Datasheet present (10 points)
        if (!empty($criteria['datasheet_present'])) {
            $score += $this->weights['datasheet_present'];
        }

        // Required specs complete (20 points)
        $specScore = $this->calculateSpecScore($criteria);
        $score += $specScore;

        // Image present (5 points)
        if (!empty($criteria['image_present'])) {
            $score += $this->weights['image_present'];
        }

        // Lifecycle known (5 points)
        if (!empty($criteria['lifecycle_present'])) {
            $score += $this->weights['lifecycle_known'];
        }

        // Compliance known (5 points)
        if (!empty($criteria['compliance_present'])) {
            $score += $this->weights['compliance_known'];
        }

        return min(100, max(0, $score));
    }

    /**
     * Calculate specification completeness score
     */
    protected function calculateSpecScore(array $criteria): float
    {
        $attributesCount = $criteria['attributes_count'] ?? 0;
        
        // Score based on attribute count (max 20 points)
        // Assume 5+ attributes is excellent, 0 is poor
        if ($attributesCount >= 5) {
            return $this->weights['required_specs_complete'];
        } elseif ($attributesCount >= 3) {
            return $this->weights['required_specs_complete'] * 0.7;
        } elseif ($attributesCount >= 1) {
            return $this->weights['required_specs_complete'] * 0.4;
        }
        
        return 0;
    }

    /**
     * Get quality classification based on score
     */
    public function getClassification(int $score): string
    {
        if ($score >= 90) {
            return 'publish_ready';
        } elseif ($score >= 70) {
            return 'review_recommended';
        } elseif ($score >= 40) {
            return 'incomplete';
        } else {
            return 'reject_quarantine';
        }
    }

    /**
     * Get detailed breakdown of score
     */
    public function getBreakdown(array $criteria): array
    {
        $breakdown = [];
        $totalScore = 0;

        foreach ($this->weights as $criterion => $weight) {
            $earned = 0;
            
            switch ($criterion) {
                case 'manufacturer_matched':
                    $earned = !empty($criteria['manufacturer_matched']) ? $weight : 0;
                    break;
                case 'mpn_valid':
                    $earned = !empty($criteria['mpn_present']) ? $weight : 0;
                    break;
                case 'category_matched':
                    $earned = !empty($criteria['category_matched']) ? $weight : 0;
                    break;
                case 'name_present':
                    $earned = !empty($criteria['name_present']) ? $weight : 0;
                    break;
                case 'description_present':
                    $earned = !empty($criteria['description_present']) ? $weight : 0;
                    break;
                case 'datasheet_present':
                    $earned = !empty($criteria['datasheet_present']) ? $weight : 0;
                    break;
                case 'required_specs_complete':
                    $earned = $this->calculateSpecScore($criteria);
                    break;
                case 'image_present':
                    $earned = !empty($criteria['image_present']) ? $weight : 0;
                    break;
                case 'lifecycle_known':
                    $earned = !empty($criteria['lifecycle_present']) ? $weight : 0;
                    break;
                case 'compliance_known':
                    $earned = !empty($criteria['compliance_present']) ? $weight : 0;
                    break;
            }

            $breakdown[$criterion] = [
                'max' => $weight,
                'earned' => $earned,
                'percentage' => $weight > 0 ? round(($earned / $weight) * 100) : 0
            ];

            $totalScore += $earned;
        }

        return [
            'total_score' => $totalScore,
            'classification' => $this->getClassification($totalScore),
            'breakdown' => $breakdown
        ];
    }

    /**
     * Validate specific field formats
     */
    public function validateField(string $field, $value): array
    {
        $errors = [];

        switch ($field) {
            case 'manufacturer_part_number':
                if (empty($value)) {
                    $errors[] = 'MPN is required';
                } elseif (strlen($value) > 100) {
                    $errors[] = 'MPN too long (max 100 characters)';
                }
                break;

            case 'datasheet_url':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $errors[] = 'Invalid URL format';
                }
                break;

            case 'price':
                if (!empty($value) && !is_numeric($value)) {
                    $errors[] = 'Price must be numeric';
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Invalid email format';
                }
                break;
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }
}
