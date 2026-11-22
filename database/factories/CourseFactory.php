<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('IF###'),
            'name' => ucwords(fake()->words(3, true)),
            'credits' => 3,
            'prerequisite_course_id' => null,
        ];
    }

    /**
     * A course that can only be taken after passing the given course.
     */
    public function requires(Course $prerequisite): static
    {
        return $this->state(fn (array $attributes) => [
            'prerequisite_course_id' => $prerequisite->id,
        ]);
    }
}
