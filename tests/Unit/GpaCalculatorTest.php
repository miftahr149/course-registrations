<?php

use App\Services\GpaCalculator;

test('the GPA is weighted by credits', function () {
    $gpa = (new GpaCalculator)->calculate([
        ['grade_point' => 4.00, 'credits' => 3],
        ['grade_point' => 3.00, 'credits' => 2],
        ['grade_point' => 2.00, 'credits' => 4],
    ]);

    // (12 + 6 + 8) / 9 = 2.888...
    expect($gpa)->toBe(2.89);
});

test('a single course GPA equals its grade point', function () {
    expect((new GpaCalculator)->calculate([['grade_point' => 3.50, 'credits' => 3]]))->toBe(3.5);
});

test('the GPA is null when there are no graded courses', function () {
    expect((new GpaCalculator)->calculate([]))->toBeNull();
});
