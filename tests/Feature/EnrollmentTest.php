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
    $this->semester = Semester::factory()->active()->create();
    $this->student = User::factory()->create();
});

/**
 * A section in the active semester that meets once, on the given day and time.
 */
function openSection(Semester $semester, array $attributes = [], int $day = 1, string $start = '08:00', string $end = '10:30'): CourseSection
{
    $section = CourseSection::factory()->create(['semester_id' => $semester->id, ...$attributes]);
    SectionSchedule::factory()->at($day, $start, $end)->create(['course_section_id' => $section->id]);

    return $section;
}

function enrollAs(User $student, CourseSection $section): Enrollment
{
    return Enrollment::factory()->create(['student_id' => $student->id, 'course_section_id' => $section->id]);
}

test('a student can enroll in an open section', function () {
    $section = openSection($this->semester);

    $this->actingAs($this->student)
        ->postJson(route('enrollments.store', $section))
        ->assertCreated()
        ->assertJsonPath('data.status', 'enrolled');

    $this->assertDatabaseHas('enrollments', [
        'student_id' => $this->student->id,
        'course_section_id' => $section->id,
        'status' => 'enrolled',
    ]);
});

test('a guest cannot enroll', function () {
    $this->postJson(route('enrollments.store', openSection($this->semester)))->assertUnauthorized();
});

test('a lecturer cannot enroll', function () {
    $this->actingAs(User::factory()->lecturer()->create())
        ->postJson(route('enrollments.store', openSection($this->semester)))
        ->assertForbidden();
});

test('a full section rejects the next student', function () {
    $section = openSection($this->semester, ['capacity' => 2]);
    enrollAs(User::factory()->create(), $section);
    enrollAs(User::factory()->create(), $section);

    $this->actingAs($this->student)
        ->postJson(route('enrollments.store', $section))
        ->assertUnprocessable()
        ->assertJsonPath('message', 'This section is full.');

    expect($section->enrolledCount())->toBe(2);
});

test('a dropped student frees the seat', function () {
    $section = openSection($this->semester, ['capacity' => 1]);
    Enrollment::factory()->dropped()->create(['course_section_id' => $section->id]);

    $this->actingAs($this->student)
        ->postJson(route('enrollments.store', $section))
        ->assertCreated();
});

test('a student cannot enroll twice in the same section', function () {
    $section = openSection($this->semester);
    enrollAs($this->student, $section);

    $this->actingAs($this->student)
        ->postJson(route('enrollments.store', $section))
        ->assertUnprocessable()
        ->assertJsonPath('message', 'You are already enrolled in this section.');
});

test('a student cannot take two sections of the same course in one semester', function () {
    $course = Course::factory()->create();
    enrollAs($this->student, openSection($this->semester, ['course_id' => $course->id, 'section_code' => 'A'], day: 1));
    $sectionB = openSection($this->semester, ['course_id' => $course->id, 'section_code' => 'B'], day: 3);

    $this->actingAs($this->student)
        ->postJson(route('enrollments.store', $sectionB))
        ->assertUnprocessable()
        ->assertJsonPath('message', "You are already enrolled in another section of {$course->code}.");
});

test('a clashing schedule is rejected', function () {
    enrollAs($this->student, openSection($this->semester, day: 1, start: '08:00', end: '10:30'));
    $clashing = openSection($this->semester, day: 1, start: '10:00', end: '12:00');

    $this->actingAs($this->student)
        ->postJson(route('enrollments.store', $clashing))
        ->assertUnprocessable()
        ->assertJsonPath('message', 'This section clashes with your schedule.');
});

test('a section that clashes only in its second meeting is rejected', function () {
    enrollAs($this->student, openSection($this->semester, day: 4, start: '13:00', end: '15:00'));
    $section = openSection($this->semester, day: 1, start: '08:00', end: '10:00');
    SectionSchedule::factory()->at(4, '14:00', '16:00')->create(['course_section_id' => $section->id]);

    $this->actingAs($this->student)
        ->postJson(route('enrollments.store', $section))
        ->assertUnprocessable();
});

test('a section right after another one is accepted', function () {
    enrollAs($this->student, openSection($this->semester, day: 1, start: '08:00', end: '10:30'));

    $this->actingAs($this->student)
        ->postJson(route('enrollments.store', openSection($this->semester, day: 1, start: '10:30', end: '12:00')))
        ->assertCreated();
});

test('a dropped section does not cause a clash', function () {
    $old = openSection($this->semester, day: 1, start: '08:00', end: '10:30');
    Enrollment::factory()->dropped()->create(['student_id' => $this->student->id, 'course_section_id' => $old->id]);

    $this->actingAs($this->student)
        ->postJson(route('enrollments.store', openSection($this->semester, day: 1, start: '09:00', end: '11:00')))
        ->assertCreated();
});

test('the prerequisite must be passed first', function (?float $prerequisiteScore, int $status) {
    $prerequisite = Course::factory()->create();
    if ($prerequisiteScore !== null) {
        $pastSection = CourseSection::factory()->create(['course_id' => $prerequisite->id, 'semester_id' => Semester::factory()]);
        Grade::factory()->score($prerequisiteScore)->create(['enrollment_id' => enrollAs($this->student, $pastSection)->id]);
    }
    $section = openSection($this->semester, ['course_id' => Course::factory()->requires($prerequisite)]);

    $this->actingAs($this->student)
        ->postJson(route('enrollments.store', $section))
        ->assertStatus($status);
})->with([
    'never taken' => [null, 422],
    'failed with D' => [50, 422],
    'passed with C' => [60, 201],
]);

test('a new student cannot go over 20 credits', function () {
    foreach ([1, 2, 3, 4, 5, 6] as $day) {
        enrollAs($this->student, openSection($this->semester, ['course_id' => Course::factory()->create(['credits' => 3])], day: $day));
    }
    // 18 SKS taken: 2 more fit, 3 more do not.
    $tooBig = openSection($this->semester, ['course_id' => Course::factory()->create(['credits' => 3])], day: 7);
    $fits = openSection($this->semester, ['course_id' => Course::factory()->create(['credits' => 2])], day: 7, start: '13:00', end: '15:00');

    $this->actingAs($this->student)
        ->postJson(route('enrollments.store', $tooBig))
        ->assertUnprocessable()
        ->assertJsonPath('message', 'This section would exceed your credit limit of 20 SKS.');
    $this->actingAs($this->student)
        ->postJson(route('enrollments.store', $fits))
        ->assertCreated();
});

test('registration is closed for an inactive semester', function () {
    $section = openSection(Semester::factory()->create());

    $this->actingAs($this->student)
        ->postJson(route('enrollments.store', $section))
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Registration for this semester is closed.');
});

test('a dropped student can enroll in the same section again', function () {
    $section = openSection($this->semester);
    $enrollment = Enrollment::factory()->dropped()->create(['student_id' => $this->student->id, 'course_section_id' => $section->id]);

    $this->actingAs($this->student)
        ->postJson(route('enrollments.store', $section))
        ->assertCreated();

    expect($enrollment->fresh()->status)->toBe(EnrollmentStatus::Enrolled)
        ->and(Enrollment::count())->toBe(1);
});

test('a student can drop their own enrollment', function () {
    $enrollment = enrollAs($this->student, openSection($this->semester));

    $this->actingAs($this->student)
        ->deleteJson(route('enrollments.destroy', $enrollment))
        ->assertOk();

    expect($enrollment->fresh()->status)->toBe(EnrollmentStatus::Dropped);
});

test('a student cannot drop or view another student\'s enrollment', function () {
    $enrollment = enrollAs(User::factory()->create(), openSection($this->semester));

    $this->actingAs($this->student)->deleteJson(route('enrollments.destroy', $enrollment))->assertForbidden();
    $this->actingAs($this->student)->getJson(route('enrollments.show', $enrollment))->assertForbidden();

    expect($enrollment->fresh()->status)->toBe(EnrollmentStatus::Enrolled);
});

test('a student sees only their own enrollments', function () {
    $mine = enrollAs($this->student, openSection($this->semester));
    enrollAs(User::factory()->create(), openSection($this->semester));

    $this->actingAs($this->student)
        ->getJson(route('enrollments.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $mine->id);
});
