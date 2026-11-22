<?php

use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Semester;
use App\Models\User;

test('a student sees the open sections with the seats left', function () {
    $section = CourseSection::factory()->create(['capacity' => 3]);
    Enrollment::factory()->count(2)->create(['course_section_id' => $section->id]);
    Enrollment::factory()->dropped()->create(['course_section_id' => $section->id]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('sections.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $section->id)
        ->assertJsonPath('data.0.seats_left', 1);
});

test('sections of an inactive semester are not listed', function () {
    CourseSection::factory()->create(['semester_id' => Semester::factory()]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('sections.index'))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('a guest cannot list sections', function () {
    $this->getJson(route('sections.index'))->assertUnauthorized();
});
