<?php

namespace Database\Factories;

use App\Enums\MeetingType;
use App\Models\CourseSection;
use App\Models\SectionSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SectionSchedule>
 */
class SectionScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_section_id' => CourseSection::factory(),
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '10:30:00',
            'meeting_type' => MeetingType::Lecture,
            'room' => fake()->bothify('R-###'),
        ];
    }

    /**
     * A meeting on the given day (1 = Monday .. 7 = Sunday) and time range.
     */
    public function at(int $dayOfWeek, string $startTime, string $endTime): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }
}
