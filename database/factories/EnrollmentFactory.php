<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => User::factory(),
            'course_section_id' => CourseSection::factory(),
            'status' => EnrollmentStatus::Enrolled,
        ];
    }

    /**
     * The student dropped the section, so the seat is free again.
     */
    public function dropped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EnrollmentStatus::Dropped,
        ]);
    }
}
