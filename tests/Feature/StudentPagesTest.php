<?php

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\SectionSchedule;
use App\Models\Semester;
use App\Models\User;

beforeEach(function () {
    $this->semester = Semester::factory()->active()->create(['name' => 'Ganjil 2026/2027']);
    $this->student = User::factory()->create();
});

/**
 * A section of a new course in the active semester, meeting once.
 */
function sectionOf(Semester $semester, array $courseAttributes = [], array $sectionAttributes = [], int $day = 1, string $start = '08:00', string $end = '10:30'): CourseSection
{
    $section = CourseSection::factory()->create([
        'course_id' => Course::factory()->create($courseAttributes)->id,
        'semester_id' => $semester->id,
        ...$sectionAttributes,
    ]);
    SectionSchedule::factory()->at($day, $start, $end)->create(['course_section_id' => $section->id]);

    return $section;
}

test('a guest is sent to the login page', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with(['sections.index', 'enrollments.index', 'grades.index']);

test('the sections page lists the open semester with seats and an enroll button', function () {
    $section = sectionOf($this->semester, ['code' => 'IF101', 'name' => 'Algoritma'], ['capacity' => 30]);
    Enrollment::factory()->create(['course_section_id' => $section->id]);
    sectionOf(Semester::factory()->create(), ['code' => 'OLD101']);

    $this->actingAs($this->student)
        ->get(route('sections.index'))
        ->assertOk()
        ->assertSee('Ganjil 2026/2027')
        ->assertSee('IF101')
        ->assertSee('1 / 30')
        ->assertSee(route('enrollments.store', $section))
        ->assertDontSee('OLD101');
});

test('a section the student is enrolled in shows as enrolled instead of an enroll button', function () {
    $section = sectionOf($this->semester);
    Enrollment::factory()->create(['student_id' => $this->student->id, 'course_section_id' => $section->id]);

    $this->actingAs($this->student)
        ->get(route('sections.index'))
        ->assertSee('Enrolled')
        ->assertDontSee(route('enrollments.store', $section));
});

test('a lecturer sees the sections without enroll buttons', function () {
    $section = sectionOf($this->semester);

    $this->actingAs(User::factory()->lecturer()->create())
        ->get(route('sections.index'))
        ->assertOk()
        ->assertSee($section->course->code)
        ->assertDontSee(route('enrollments.store', $section));
});

test('enrolling from the page redirects back with a success message', function () {
    $section = sectionOf($this->semester, ['code' => 'IF101'], ['section_code' => 'A']);

    $this->actingAs($this->student)
        ->from(route('sections.index'))
        ->post(route('enrollments.store', $section))
        ->assertRedirect(route('sections.index'))
        ->assertSessionHas('status', 'Enrolled in IF101 section A.');

    $this->assertDatabaseHas('enrollments', [
        'student_id' => $this->student->id,
        'course_section_id' => $section->id,
        'status' => EnrollmentStatus::Enrolled->value,
    ]);
});

test('a rejected enrollment redirects back with the reason', function () {
    $section = sectionOf($this->semester, sectionAttributes: ['capacity' => 1]);
    Enrollment::factory()->create(['course_section_id' => $section->id]);

    $this->actingAs($this->student)
        ->from(route('sections.index'))
        ->post(route('enrollments.store', $section))
        ->assertRedirect(route('sections.index'))
        ->assertSessionHas('error', 'This section is full.');

    $this->assertDatabaseMissing('enrollments', ['student_id' => $this->student->id]);
});

test('the KRS page shows the current enrollments and the credits taken', function () {
    $current = sectionOf($this->semester, ['code' => 'IF201', 'credits' => 3]);
    $dropped = sectionOf($this->semester, ['code' => 'IF999'], day: 2);
    $past = sectionOf(Semester::factory()->create(), ['code' => 'IF100']);
    Enrollment::factory()->create(['student_id' => $this->student->id, 'course_section_id' => $current->id]);
    Enrollment::factory()->dropped()->create(['student_id' => $this->student->id, 'course_section_id' => $dropped->id]);
    Enrollment::factory()->create(['student_id' => $this->student->id, 'course_section_id' => $past->id]);

    $this->actingAs($this->student)
        ->get(route('enrollments.index'))
        ->assertOk()
        ->assertSee('IF201')
        ->assertDontSee('IF999')
        ->assertDontSee('IF100')
        ->assertSee('3 / 20 SKS');
});

test('dropping from the page redirects back with a success message', function () {
    $section = sectionOf($this->semester, ['code' => 'IF201'], ['section_code' => 'B']);
    $enrollment = Enrollment::factory()->create(['student_id' => $this->student->id, 'course_section_id' => $section->id]);

    $this->actingAs($this->student)
        ->from(route('enrollments.index'))
        ->delete(route('enrollments.destroy', $enrollment))
        ->assertRedirect(route('enrollments.index'))
        ->assertSessionHas('status', 'Dropped IF201 section B.');

    expect($enrollment->fresh()->status)->toBe(EnrollmentStatus::Dropped);
});

test('the KHS page shows the grades and the GPA', function () {
    $section = sectionOf(Semester::factory()->create(['name' => 'Genap 2025/2026']), ['code' => 'IF101', 'credits' => 4]);
    $enrollment = Enrollment::factory()->create(['student_id' => $this->student->id, 'course_section_id' => $section->id]);
    Grade::factory()->create(['enrollment_id' => $enrollment->id, 'score' => 88, 'letter' => 'A', 'grade_point' => 4.00]);

    $this->actingAs($this->student)
        ->get(route('grades.index'))
        ->assertOk()
        ->assertSee('Genap 2025/2026')
        ->assertSee('IF101')
        ->assertSee('GPA: <strong>4.00</strong>', false)
        ->assertSee('24 SKS');
});
