<?php

namespace App\Services;

/**
 * The letter and grade point that a numeric score converts to.
 */
final readonly class GradeResult
{
    public function __construct(
        public string $letter,
        public float $gradePoint,
    ) {}
}
