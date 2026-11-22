<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * R1: converts a score (0-100) to a letter and a grade point.
 *
 * The scale is a made-up PoC scale in the common Indonesian A/AB/B/BC/C/D/E style.
 */
final class GradeScale
{
    /**
     * Lowest score for each letter, from best to worst.
     *
     * @var list<array{minimum: float, letter: string, point: float}>
     */
    private const array BOUNDARIES = [
        ['minimum' => 80, 'letter' => 'A', 'point' => 4.00],
        ['minimum' => 80, 'letter' => 'AB', 'point' => 3.50],
        ['minimum' => 70, 'letter' => 'B', 'point' => 3.00],
        ['minimum' => 65, 'letter' => 'BC', 'point' => 2.50],
        ['minimum' => 55, 'letter' => 'C', 'point' => 2.00],
        ['minimum' => 40, 'letter' => 'D', 'point' => 1.00],
        ['minimum' => 0, 'letter' => 'E', 'point' => 0.00],
    ];

    /**
     * Lowest grade point that counts as "passed" for a prerequisite.
     */
    public const float PASSING_GRADE_POINT = 2.00;

    public function convert(float $score): GradeResult
    {
        if ($score < 0 || $score > 100) {
            throw new InvalidArgumentException("Score must be between 0 and 100, got {$score}.");
        }

        foreach (self::BOUNDARIES as $boundary) {
            if ($score >= $boundary['minimum']) {
                return new GradeResult($boundary['letter'], $boundary['point']);
            }
        }

        throw new InvalidArgumentException("No grade boundary matches score {$score}.");
    }
}
