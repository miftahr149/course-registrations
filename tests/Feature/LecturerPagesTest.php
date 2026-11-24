<?php

use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\User;

beforeEach(function () {
    $this->lecturer = User::factory()->lecturer()->create();
    $this->section = CourseSection::factory()->create(['lecturer_id' => $this->lecturer->id]);
});

test('a lecturer sees the sections they teach', function () {
    $otherSection = CourseSection::factory()->create();

    $this->actingAs($this->lecturer)
        ->get(route('teaching.index'))
        ->assertOk()
        ->assertSee(route('teaching.show', $this->section))
        ->assertDontSee(route('teaching.show', $otherSection));
});

test('only lecturers can open the teaching page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('teaching.index'))
        ->assertForbidden();
});

test('the section page lists its enrolled students with their grades', function () {
    $graded = Enrollment::factory()->create([
        'course_section_id' => $this->section->id,
        'student_id' => User::factory()->create(['name' => 'Raka'])->id,
    ]);
    Grade::factory()->create(['enrollment_id' => $graded->id, 'score' => 88, 'letter' => 'A', 'grade_point' => 4.00]);
    Enrollment::factory()->create([
        'course_section_id' => $this->section->id,
        'student_id' => User::factory()->create(['name' => 'Dewi'])->id,
    ]);
    Enrollment::factory()->dropped()->create([
        'course_section_id' => $this->section->id,
        'student_id' => User::factory()->create(['name' => 'Dropped Student'])->id,
    ]);

    $this->actingAs($this->lecturer)
        ->get(route('teaching.show', $this->section))
        ->assertOk()
        ->assertSeeInOrder(['Dewi', 'Not graded', 'Raka', 'A (4.00)'])
        ->assertSee(route('grades.update', $graded))
        ->assertDontSee('Dropped Student');
});

test('another lecturer cannot open the section page', function () {
    $this->actingAs(User::factory()->lecturer()->create())
        ->get(route('teaching.show', $this->section))
        ->assertForbidden();
});

test('only the section lecturer may view its roster', function (Closure $makeUser, bool $allowed) {
    expect($makeUser($this->section)->can('viewRoster', $this->section))->toBe($allowed);
})->with([
    'the section lecturer' => [fn (CourseSection $section) => $section->lecturer, true],
    'another lecturer' => [fn () => User::factory()->lecturer()->create(), false],
    'a student' => [fn () => User::factory()->create(), false],
    'an admin' => [fn () => User::factory()->admin()->create(), false],
]);

test('saving a score from the page redirects back with the letter grade', function () {
    $enrollment = Enrollment::factory()->create([
        'course_section_id' => $this->section->id,
        'student_id' => User::factory()->create(['name' => 'Raka'])->id,
    ]);

    $this->actingAs($this->lecturer)
        ->from(route('teaching.show', $this->section))
        ->put(route('grades.update', $enrollment), ['score' => 82])
        ->assertRedirect(route('teaching.show', $this->section))
        ->assertSessionHas('status', "Saved Raka's grade: AB.");

    $this->assertDatabaseHas('grades', ['enrollment_id' => $enrollment->id, 'letter' => 'AB']);
});

test('an invalid score from the page redirects back with a validation error', function () {
    $enrollment = Enrollment::factory()->create(['course_section_id' => $this->section->id]);

    $this->actingAs($this->lecturer)
        ->from(route('teaching.show', $this->section))
        ->put(route('grades.update', $enrollment), ['score' => 101])
        ->assertRedirect(route('teaching.show', $this->section))
        ->assertSessionHasErrors(['score' => 'The score field must be between 0 and 100.']);

    $this->assertDatabaseMissing('grades', ['enrollment_id' => $enrollment->id]);
});

test('grading a dropped enrollment from the page redirects back with the reason', function () {
    $enrollment = Enrollment::factory()->dropped()->create(['course_section_id' => $this->section->id]);

    $this->actingAs($this->lecturer)
        ->from(route('teaching.show', $this->section))
        ->put(route('grades.update', $enrollment), ['score' => 80])
        ->assertRedirect(route('teaching.show', $this->section))
        ->assertSessionHas('error', 'A dropped enrollment cannot be graded.');
});
