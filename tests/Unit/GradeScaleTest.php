<?php

use App\Services\GradeScale;

test('a score converts to the right letter and grade point', function (float $score, string $letter, float $gradePoint) {
    $result = (new GradeScale)->convert($score);

    expect($result->letter)->toBe($letter)
        ->and($result->gradePoint)->toBe($gradePoint);
})->with([
    'top score' => [100, 'A', 4.00],
    'A lower boundary' => [85, 'A', 4.00],
    'just below A' => [84.99, 'AB', 3.50],
    'AB lower boundary' => [80, 'AB', 3.50],
    'just below AB' => [79.99, 'B', 3.00],
    'B lower boundary' => [70, 'B', 3.00],
    'BC lower boundary' => [65, 'BC', 2.50],
    'C lower boundary' => [55, 'C', 2.00],
    'just below C' => [54.99, 'D', 1.00],
    'D lower boundary' => [40, 'D', 1.00],
    'just below D' => [39.99, 'E', 0.00],
    'zero' => [0, 'E', 0.00],
]);

test('a score outside 0-100 is rejected', function (float $score) {
    (new GradeScale)->convert($score);
})->with([-0.01, 100.01])->throws(InvalidArgumentException::class);
