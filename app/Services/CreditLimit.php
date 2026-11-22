<?php

namespace App\Services;

/**
 * R3: the maximum credits (SKS) a student may take, based on the GPA so far.
 *
 * PoC rule: GPA >= 3.00 -> 24 SKS, lower -> 18 SKS, no grades yet (new student) -> 20 SKS.
 */
final class CreditLimit
{
    public const int NEW_STUDENT = 20;

    public const int HIGH_GPA = 24;

    public const int LOW_GPA = 18;

    public const float HIGH_GPA_THRESHOLD = 3.00;

    public function forGpa(?float $gpa): int
    {
        if ($gpa === null) {
            return self::NEW_STUDENT;
        }

        return $gpa >= self::HIGH_GPA_THRESHOLD ? self::HIGH_GPA : self::LOW_GPA;
    }
}
