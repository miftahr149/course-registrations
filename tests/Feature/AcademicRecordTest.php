<?php

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\User;
use App\Services\AcademicRecord;

function gradeStudent(User $student, int $credits, float $score, bool $dropped = false): Course
{
    $course = Course::factory()->create(['credits' => $credits]);
    $enrollment = Enrollment::factory()
        ->when($dropped, fn ($factory) => $factory->dropped())
        ->create([
            'student_id' => $student->id,
            'course_section_id' => CourseSection::factory()->create(['course_id' => $course->id])->id,
        ]);
    Grade::factory()->score($score)->create(['enrollment_id' => $enrollment->id]);

    return $course;
}

test('the GPA is computed from the stored grades and course credits', function () {
    $student = User::factory()->create();
    gradeStudent($student, credits: 3, score: 90); // A  4.00
    gradeStudent($student, credits: 2, score: 72); // B  3.00

    // (12 + 6) / 5 = 3.60
    expect(app(AcademicRecord::class)->gpaFor($student))->toBe(3.6);
});

test('dropped enrollments and other students do not count toward the GPA', function () {
    $student = User::factory()->create();
    gradeStudent($student, credits: 3, score: 90);
    gradeStudent($student, credits: 3, score: 10, dropped: true);
    gradeStudent(User::factory()->create(), credits: 3, score: 10);

    expect(app(AcademicRecord::class)->gpaFor($student))->toBe(4.0);
});

test('the credit limit follows the stored GPA', function () {
    $newStudent = User::factory()->create();
    $strongStudent = User::factory()->create();
    gradeStudent($strongStudent, credits: 3, score: 90);
    $weakStudent = User::factory()->create();
    gradeStudent($weakStudent, credits: 3, score: 60);

    $record = app(AcademicRecord::class);

    expect($record->creditLimitFor($newStudent))->toBe(20)
        ->and($record->creditLimitFor($strongStudent))->toBe(24)
        ->and($record->creditLimitFor($weakStudent))->toBe(18);
});

test('a course counts as passed only with grade C or better', function () {
    $student = User::factory()->create();
    $passed = gradeStudent($student, credits: 3, score: 55);  // C
    $failed = gradeStudent($student, credits: 3, score: 54);  // D
    $notTaken = Course::factory()->create();

    $record = app(AcademicRecord::class);

    expect($record->hasPassed($student, $passed->id))->toBeTrue()
        ->and($record->hasPassed($student, $failed->id))->toBeFalse()
        ->and($record->hasPassed($student, $notTaken->id))->toBeFalse();
});
