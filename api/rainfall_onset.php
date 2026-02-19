<?php
// api/rainfall_onset.php

class RainfallOnsetDetector
{

    /**
     * Main function to detect rainfall onset for Cameroon
     * Based on agrometeorological standards for West Africa
     */
    public function detectOnset($precipitation_data, $region_type = 'equatorial')
    {
        // $precipitation_data format: [['date' => '2024-01-01', 'precip' => 12.5], ...]

        if (empty($precipitation_data)) {
            return null;
        }

        // Step 1: Apply region-specific thresholds
        $thresholds = $this->getRegionThresholds($region_type);

        // Step 2: Smooth data with 5-day moving average
        $smoothed_data = $this->applyMovingAverage($precipitation_data, 5);

        // Step 3: Identify potential onset periods
        $potential_onsets = $this->findPotentialOnsets($smoothed_data, $thresholds);

        // Step 4: Apply agronomic rules
        $valid_onsets = $this->applyAgronomicRules($potential_onsets, $thresholds, $precipitation_data);

        // Step 5: Determine the most likely onset
        $final_onset = $this->determineFinalOnset($valid_onsets, $precipitation_data);

        return $final_onset;
    }

    private function getRegionThresholds($region_type)
    {
        $region_type = strtolower($region_type);

        // Region-specific thresholds for Cameroon
        $thresholds = [
            'equatorial' => [ // Douala, Yaoundé, South
                'daily_threshold' => 1.0,    // mm/day
                'consecutive_days' => 3,
                'dry_spell_max' => 7,        // max dry days after onset
                'total_rain' => 25,          // mm in 10 days after onset
            ],
            'sudano_guinean' => [ // West, Northwest
                'daily_threshold' => 0.5,
                'consecutive_days' => 4,
                'dry_spell_max' => 10,
                'total_rain' => 20,
            ],
            'sudanian' => [ // Adamawa
                'daily_threshold' => 0.5,
                'consecutive_days' => 5,
                'dry_spell_max' => 8,
                'total_rain' => 15,
            ],
            'sahelian' => [ // Far North
                'daily_threshold' => 0.3,
                'consecutive_days' => 6,
                'dry_spell_max' => 5,
                'total_rain' => 10,
                'require_effective_rain' => true  // >20mm in 2 days
            ]
        ];

        return $thresholds[$region_type] ?? $thresholds['equatorial'];
    }

    private function applyMovingAverage($data, $window_size)
    {
        $smoothed = [];
        $count = count($data);

        for ($i = 0; $i < $count; $i++) {
            $sum = 0;
            $values_count = 0;

            for (
                $j = max(0, $i - floor($window_size / 2));
                $j <= min($count - 1, $i + floor($window_size / 2));
                $j++
            ) {
                $sum += (float)($data[$j]['precip'] ?? 0);
                $values_count++;
            }

            $smoothed[] = [
                'date' => $data[$i]['date'],
                'precip' => (float)($data[$i]['precip'] ?? 0),
                'smoothed' => $sum / $values_count
            ];
        }

        return $smoothed;
    }

    private function findPotentialOnsets($smoothed_data, $thresholds)
    {
        $potential_onsets = [];
        $count = count($smoothed_data);
        $consecutive_days_required = $thresholds['consecutive_days'];

        for ($i = 0; $i < $count - $consecutive_days_required + 1; $i++) {
            $consecutive = true;

            for ($j = 0; $j < $consecutive_days_required; $j++) {
                if ($smoothed_data[$i + $j]['smoothed'] < $thresholds['daily_threshold']) {
                    $consecutive = false;
                    break;
                }
            }

            if ($consecutive) {
                $dry_days_before = $this->countDryDaysBefore($smoothed_data, $i, $thresholds);

                if ($dry_days_before >= 7) {
                    $potential_onsets[] = [
                        'start_index' => $i,
                        'start_date' => $smoothed_data[$i]['date'],
                        'dry_days_before' => $dry_days_before,
                        'initial_rain' => $this->sumRainfall($smoothed_data, $i, $consecutive_days_required),
                        'smoothed_values' => array_column(
                            array_slice($smoothed_data, $i, $consecutive_days_required),
                            'smoothed'
                        )
                    ];
                }
            }
        }

        return $potential_onsets;
    }

    private function countDryDaysBefore($data, $start_index, $thresholds)
    {
        $dry_days = 0;
        $lookback = min($start_index, 30);

        for ($i = 1; $i <= $lookback; $i++) {
            if ($data[$start_index - $i]['smoothed'] < $thresholds['daily_threshold']) {
                $dry_days++;
            } else {
                break;
            }
        }

        return $dry_days;
    }

    private function applyAgronomicRules($potential_onsets, $thresholds, $all_data)
    {
        $valid_onsets = [];

        foreach ($potential_onsets as $onset) {
            $is_valid = true;
            $start_idx = $onset['start_index'];

            // Rule: Check for dry spell after onset
            if (!$this->checkDrySpellAfter($all_data, $start_idx, $thresholds)) {
                $is_valid = false;
            }

            // Rule: Check total rainfall in following 10 days
            if (!$this->checkFollowupRain($all_data, $start_idx, $thresholds)) {
                $is_valid = false;
            }

            // Rule: Sahelian effective rain check
            if ($is_valid && isset($thresholds['require_effective_rain']) && $thresholds['require_effective_rain']) {
                if (!$this->checkEffectiveRain(array_slice($all_data, $start_idx, 15), 20, 2)) {
                    $is_valid = false;
                }
            }

            if ($is_valid) {
                $valid_onsets[] = $onset;
            }
        }

        return $valid_onsets;
    }

    private function checkDrySpellAfter($data, $start_idx, $thresholds)
    {
        $max_dry = $thresholds['dry_spell_max'] ?? 10;
        $consecutive_dry = 0;
        $limit = min($start_idx + 20, count($data));

        for ($i = $start_idx; $i < $limit; $i++) {
            if (($data[$i]['precip'] ?? 0) < 1.0) {
                $consecutive_dry++;
                if ($consecutive_dry > $max_dry) return false;
            } else {
                $consecutive_dry = 0;
            }
        }
        return true;
    }

    private function checkFollowupRain($data, $start_idx, $thresholds)
    {
        $required = $thresholds['total_rain'] ?? 20;
        $sum = 0;
        $limit = min($start_idx + 10, count($data));

        for ($i = $start_idx; $i < $limit; $i++) {
            $sum += (float)($data[$i]['precip'] ?? 0);
        }
        return $sum >= $required;
    }

    private function checkEffectiveRain($slice, $threshold_mm, $days_window)
    {
        for ($i = 0; $i <= count($slice) - $days_window; $i++) {
            $sum = 0;
            for ($j = 0; $j < $days_window; $j++) {
                $sum += (float)($slice[$i + $j]['precip'] ?? 0);
            }
            if ($sum >= $threshold_mm) return true;
        }
        return false;
    }

    private function determineFinalOnset($valid_onsets, $original_data)
    {
        if (empty($valid_onsets)) {
            return $this->estimateOnsetFromHistorical($original_data);
        }

        usort($valid_onsets, function ($a, $b) {
            return strtotime($a['start_date']) - strtotime($b['start_date']);
        });

        $selected = $valid_onsets[0];
        $selected['confidence'] = $this->calculateConfidence($selected, $valid_onsets);
        $selected['detection_method'] = 'agro_climatological';
        $selected['validation_status'] = 'medium_confidence';

        // Add precision metrics
        $selected['precision_metrics'] = $this->calculatePrecisionMetrics($selected['start_index'], $original_data);

        return $selected;
    }

    private function calculatePrecisionMetrics($start_idx, $data)
    {
        $limit_10d = min($start_idx + 10, count($data));
        $limit_20d = min($start_idx + 20, count($data));

        $cum_10d = 0;
        $wet_days = 0;
        $max_dry = 0;
        $current_dry = 0;

        for ($i = $start_idx; $i < $limit_10d; $i++) {
            $p = (float)($data[$i]['precip'] ?? 0);
            $cum_10d += $p;
            if ($p >= 1.0) $wet_days++;
        }

        for ($i = $start_idx; $i < $limit_20d; $i++) {
            $p = (float)($data[$i]['precip'] ?? 0);
            if ($p < 1.0) {
                $current_dry++;
                $max_dry = max($max_dry, $current_dry);
            } else {
                $current_dry = 0;
            }
        }

        return [
            'cumulative_10d' => round($cum_10d, 1),
            'max_dry_period' => $max_dry,
            'avg_intensity' => $wet_days > 0 ? round($cum_10d / $wet_days, 1) : 0,
            'wet_days_10d' => $wet_days
        ];
    }

    private function estimateOnsetFromHistorical($data)
    {
        return [
            'start_date' => date('Y') . '-04-15', // Generic default for Cameroon
            'confidence' => 30,
            'detection_method' => 'climatological_average',
            'validation_status' => 'low_confidence',
            'note' => 'Estimated from regional averages'
        ];
    }

    private function calculateConfidence($onset, $all_onsets)
    {
        $confidence = 70;
        if ($onset['dry_days_before'] >= 14) $confidence += 10;
        if ($onset['initial_rain'] >= 20) $confidence += 15;
        if (count($all_onsets) > 1) $confidence -= 10;
        return max(0, min(100, $confidence));
    }

    private function sumRainfall($data, $idx, $days)
    {
        $sum = 0;
        $end = min($idx + $days, count($data));
        for ($i = $idx; $i < $end; $i++) {
            $sum += (float)($data[$i]['precip'] ?? $data[$i]['smoothed'] ?? 0);
        }
        return $sum;
    }
}
