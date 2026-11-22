<?php

use App\Services\TimeSlot;

test('slot clash detection', function (TimeSlot $first, TimeSlot $second, bool $clashes) {
    expect($first->overlaps($second))->toBe($clashes)
        ->and($second->overlaps($first))->toBe($clashes);
})->with([
    'same time, same day' => [new TimeSlot(1, '08:00:00', '10:00:00'), new TimeSlot(1, '08:00:00', '10:00:00'), true],
    'one minute overlap' => [new TimeSlot(1, '08:00:00', '10:00:00'), new TimeSlot(1, '09:59:00', '11:00:00'), true],
    'one inside the other' => [new TimeSlot(1, '08:00:00', '12:00:00'), new TimeSlot(1, '09:00:00', '10:00:00'), true],
    'exactly touching' => [new TimeSlot(1, '08:00:00', '10:00:00'), new TimeSlot(1, '10:00:00', '12:00:00'), false],
    'same time, different day' => [new TimeSlot(1, '08:00:00', '10:00:00'), new TimeSlot(2, '08:00:00', '10:00:00'), false],
    'different time formats' => [new TimeSlot(1, '8:00', '10:00'), new TimeSlot(1, '09:00:00', '11:00:00'), true],
]);
