<?php

use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Grade;
use Illuminate\Database\UniqueConstraintViolationException;

/*
 * These tests write straight to the database, past all application checks.
 * They prove the UNIQUE indexes still exist as the last line of defense.
 */

test('the database rejects a second enrollment of the same student in the same section', function () {
    $enrollment = Enrollment::factory()->create();

    Enrollment::factory()->create([
        'student_id' => $enrollment->student_id,
        'course_section_id' => $enrollment->course_section_id,
    ]);
})->throws(UniqueConstraintViolationException::class);

test('the database rejects two sections with the same code for one course in one semester', function () {
    $section = CourseSection::factory()->create(['section_code' => 'A']);

    CourseSection::factory()->create([
        'course_id' => $section->course_id,
        'semester_id' => $section->semester_id,
        'section_code' => 'A',
    ]);
})->throws(UniqueConstraintViolationException::class);

test('the database rejects a second grade for the same enrollment', function () {
    $grade = Grade::factory()->create();

    Grade::factory()->create(['enrollment_id' => $grade->enrollment_id]);
})->throws(UniqueConstraintViolationException::class);

test('the demo seeder runs on a fresh database', function () {
    $this->seed();

    expect(CourseSection::count())->toBe(12);
});
