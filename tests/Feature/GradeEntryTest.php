<?php

use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\User;

beforeEach(function () {
    $this->lecturer = User::factory()->lecturer()->create();
    $this->section = CourseSection::factory()->create(['lecturer_id' => $this->lecturer->id]);
    $this->enrollment = Enrollment::factory()->create(['course_section_id' => $this->section->id]);
});

test('the section lecturer can enter a grade', function () {
    $this->actingAs($this->lecturer)
        ->putJson(route('grades.update', $this->enrollment), ['score' => 82.5])
        ->assertOk()
        ->assertJsonPath('data.letter', 'AB');

    $this->assertDatabaseHas('grades', [
        'enrollment_id' => $this->enrollment->id,
        'score' => 82.5,
        'letter' => 'AB',
        'grade_point' => 3.5,
        'graded_by' => $this->lecturer->id,
    ]);
});

test('entering a grade again corrects the existing grade', function () {
    $this->actingAs($this->lecturer)->putJson(route('grades.update', $this->enrollment), ['score' => 50]);
    $this->actingAs($this->lecturer)->putJson(route('grades.update', $this->enrollment), ['score' => 90])->assertOk();

    expect(Grade::count())->toBe(1)
        ->and(Grade::first()->letter)->toBe('A');
});

test('only the section lecturer can enter grades', function (User $user) {
    $this->actingAs($user)
        ->putJson(route('grades.update', $this->enrollment), ['score' => 100])
        ->assertForbidden();

    $this->assertDatabaseMissing('grades', ['enrollment_id' => $this->enrollment->id]);
})->with([
    'another lecturer' => fn () => User::factory()->lecturer()->create(),
    'the student' => fn () => $this->enrollment->student,
    'another student' => fn () => User::factory()->create(),
]);

test('a guest cannot enter grades', function () {
    $this->putJson(route('grades.update', $this->enrollment), ['score' => 100])->assertUnauthorized();
});

test('the score must be between 0 and 100', function (mixed $score) {
    $this->actingAs($this->lecturer)
        ->putJson(route('grades.update', $this->enrollment), ['score' => $score])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('score');
})->with([-1, 100.5, 'abc', null]);

test('a dropped enrollment cannot be graded', function () {
    $this->enrollment->update(['status' => 'dropped']);

    $this->actingAs($this->lecturer)
        ->putJson(route('grades.update', $this->enrollment), ['score' => 80])
        ->assertUnprocessable();
});

test('a student sees only their own grades and GPA', function () {
    $student = $this->enrollment->student;
    Grade::factory()->score(72)->create(['enrollment_id' => $this->enrollment->id]);
    Grade::factory()->score(95)->create();

    $this->actingAs($student)
        ->getJson(route('grades.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.letter', 'B')
        ->assertJsonPath('gpa', 3);
});

test('the section lecturer can view the enrollment and its grade', function () {
    Grade::factory()->score(72)->create(['enrollment_id' => $this->enrollment->id]);

    $this->actingAs($this->lecturer)
        ->getJson(route('enrollments.show', $this->enrollment))
        ->assertOk()
        ->assertJsonPath('data.grade.letter', 'B');
});
