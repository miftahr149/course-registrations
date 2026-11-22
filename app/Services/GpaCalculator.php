<?php

namespace App\Services;

/**
 * R2: GPA = sum(grade point x credits) / sum(credits), rounded to 2 decimals.
 */
final class GpaCalculator
{
    /**
     * @param  iterable<array{grade_point: float, credits: int}>  $results
     */
    public function calculate(iterable $results): ?float
    {
        $weightedPoints = 0.0;
        $totalCredits = 0;

        foreach ($results as $result) {
            $weightedPoints += $result['grade_point'] * $result['credits'];
            $totalCredits += $result['credits'];
        }

        if ($totalCredits === 0) {
            return null;
        }

        return round($weightedPoints / $totalCredits, 2);
    }
}
