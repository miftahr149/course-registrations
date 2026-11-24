<?php

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\User;
use App\Services\AcademicRecord;

test('the demo seeder tells the story documented in DatabaseSeeder', function () {
    $this->seed();

    $record = app(AcademicRecord::class);
    $raka = User::query()->where('email', 'raka@example.com')->sole();
    $dewi = User::query()->where('email', 'dewi@example.com')->sole();
    $if101 = Course::query()->where('code', 'IF101')->sole();
    $if204A = CourseSection::query()->whereHas('course', fn ($course) => $course->where('code', 'IF204'))->sole();

    expect($record->gpaFor($raka))->toBe(3.61)
        ->and($record->creditLimitFor($raka))->toBe(24)
        ->and($record->gpaFor($dewi))->toBe(1.94)
        ->and($record->creditLimitFor($dewi))->toBe(18)
        ->and($record->hasPassed($dewi, $if101->id))->toBeFalse()
        ->and($if204A->enrolledCount())->toBe($if204A->capacity);
});
