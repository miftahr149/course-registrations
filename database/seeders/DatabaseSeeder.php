<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with a small, readable demo data set.
     * Every user's password is "password".
     */
    public function run(): void
    {
        User::factory()->admin()->create(['name' => 'Admin', 'email' => 'admin@example.com']);
        $budi = User::factory()->lecturer()->create(['name' => 'Dr. Budi', 'email' => 'budi@example.com']);
        $sari = User::factory()->lecturer()->create(['name' => 'Dr. Sari', 'email' => 'sari@example.com']);
        User::factory()->create(['name' => 'Raka', 'email' => 'raka@example.com', 'nim' => '2026000001']);
        User::factory()->create(['name' => 'Dewi', 'email' => 'dewi@example.com', 'nim' => '2026000002']);

        $semester = Semester::factory()->active()->create([
            'code' => '2026-1',
            'name' => 'Ganjil 2026/2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-01-31',
        ]);

        $algorithms = Course::factory()->create(['code' => 'IF101', 'name' => 'Algoritma dan Pemrograman', 'credits' => 4]);
        $databases = Course::factory()->requires($algorithms)->create(['code' => 'IF201', 'name' => 'Basis Data', 'credits' => 3]);
        $networks = Course::factory()->create(['code' => 'IF202', 'name' => 'Jaringan Komputer', 'credits' => 3]);

        $sections = [
            [$algorithms, 'A', $budi, 1, '08:00', '10:30'],
            [$databases, 'A', $budi, 1, '13:00', '15:30'],
            [$databases, 'B', $sari, 3, '08:00', '10:30'],
            [$networks, 'A', $sari, 1, '09:00', '11:30'],
        ];

        foreach ($sections as [$course, $code, $lecturer, $day, $start, $end]) {
            $section = CourseSection::factory()->create([
                'course_id' => $course->id,
                'semester_id' => $semester->id,
                'lecturer_id' => $lecturer->id,
                'section_code' => $code,
                'capacity' => 30,
            ]);

            $section->schedules()->create([
                'day_of_week' => $day,
                'start_time' => $start,
                'end_time' => $end,
                'meeting_type' => 'lecture',
                'room' => "R-{$day}0{$code}",
            ]);
        }
    }
}
