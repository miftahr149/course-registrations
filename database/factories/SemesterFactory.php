<?php

namespace Database\Factories;

use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Semester>
 */
class SemesterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(2000, 2099);

        return [
            'code' => "{$year}-1",
            'name' => "Ganjil {$year}/".($year + 1),
            'start_date' => "{$year}-09-01",
            'end_date' => ($year + 1).'-01-31',
            'is_active' => false,
        ];
    }

    /**
     * The semester students are currently registering for.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
