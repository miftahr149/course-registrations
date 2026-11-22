<?php

use App\Services\CreditLimit;

test('the credit limit depends on the GPA', function (?float $gpa, int $limit) {
    expect((new CreditLimit)->forGpa($gpa))->toBe($limit);
})->with([
    'new student' => [null, 20],
    'perfect GPA' => [4.00, 24],
    'exactly the threshold' => [3.00, 24],
    'just below the threshold' => [2.99, 18],
    'zero GPA' => [0.00, 18],
]);
