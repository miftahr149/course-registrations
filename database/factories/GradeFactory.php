<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\User;
use App\Services\GradeScale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grade>
 */
class GradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'score' => 85,
            'letter' => 'A',
            'grade_point' => 4.00,
            'graded_by' => User::factory()->lecturer(),
            'graded_at' => now(),
        ];
    }

    /**
     * Set the score and derive the letter and grade point from the real grade scale.
     */
    public function score(float $score): static
    {
        $result = (new GradeScale)->convert($score);

        return $this->state(fn (array $attributes) => [
            'score' => $score,
            'letter' => $result->letter,
            'grade_point' => $result->gradePoint,
        ]);
    }
}
