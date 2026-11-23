<?php

namespace Database\Seeders;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\User;
use App\Services\GradeScale;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * A small, readable demo data set. Every user's password is "password".
 *
 * Story behind the data:
 * - Last semester (2025-2, closed) Raka did well (GPA 3.61 -> 24 SKS limit),
 *   Dewi did poorly and failed IF101 (GPA 1.94 -> 18 SKS limit, can't take IF201/IF203).
 * - Andi, Putri and Fajar are new students (no GPA -> 20 SKS limit).
 * - This semester (2026-1, open): IF204 A is full, IF202 A clashes with IF101 A,
 *   and IF301 needs IF203, which nobody has passed yet. Nothing in 2026-1 is graded yet.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function __construct(private GradeScale $gradeScale) {}

    public function run(): void
    {
        User::factory()->admin()->create(['name' => 'Admin', 'email' => 'admin@example.com']);
        $budi = User::factory()->lecturer()->create(['name' => 'Dr. Budi', 'email' => 'budi@example.com']);
        $sari = User::factory()->lecturer()->create(['name' => 'Dr. Sari', 'email' => 'sari@example.com']);

        $raka = User::factory()->create(['name' => 'Raka', 'email' => 'raka@example.com', 'nim' => '2025000001']);
        $dewi = User::factory()->create(['name' => 'Dewi', 'email' => 'dewi@example.com', 'nim' => '2025000002']);
        $andi = User::factory()->create(['name' => 'Andi', 'email' => 'andi@example.com', 'nim' => '2026000001']);
        $putri = User::factory()->create(['name' => 'Putri', 'email' => 'putri@example.com', 'nim' => '2026000002']);
        $fajar = User::factory()->create(['name' => 'Fajar', 'email' => 'fajar@example.com', 'nim' => '2026000003']);

        // Course catalog
        $if101 = Course::factory()->create(['code' => 'IF101', 'name' => 'Algoritma dan Pemrograman', 'credits' => 4]);
        $if102 = Course::factory()->create(['code' => 'IF102', 'name' => 'Matematika Diskrit', 'credits' => 3]);
        $if103 = Course::factory()->create(['code' => 'IF103', 'name' => 'Pengantar Teknologi Informasi', 'credits' => 2]);
        $if201 = Course::factory()->requires($if101)->create(['code' => 'IF201', 'name' => 'Basis Data', 'credits' => 3]);
        $if202 = Course::factory()->create(['code' => 'IF202', 'name' => 'Jaringan Komputer', 'credits' => 3]);
        $if203 = Course::factory()->requires($if101)->create(['code' => 'IF203', 'name' => 'Struktur Data', 'credits' => 3]);
        $if204 = Course::factory()->create(['code' => 'IF204', 'name' => 'Pemrograman Web', 'credits' => 3]);
        $if205 = Course::factory()->create(['code' => 'IF205', 'name' => 'Sistem Operasi', 'credits' => 3]);
        $if301 = Course::factory()->requires($if203)->create(['code' => 'IF301', 'name' => 'Rekayasa Perangkat Lunak', 'credits' => 3]);

        // Last semester: closed and fully graded.
        $past = Semester::factory()->create([
            'code' => '2025-2',
            'name' => 'Genap 2025/2026',
            'start_date' => '2026-02-01',
            'end_date' => '2026-06-30',
        ]);

        $pastIf101 = $this->section($if101, $past, 'A', $budi, 30, [[1, '08:00', '10:30', 'lecture', 'R-101']]);
        $pastIf102 = $this->section($if102, $past, 'A', $sari, 30, [[2, '08:00', '10:30', 'lecture', 'R-102']]);
        $pastIf103 = $this->section($if103, $past, 'A', $budi, 30, [[3, '10:00', '11:40', 'lecture', 'R-103']]);

        $this->enroll($raka, $pastIf101, 88, $budi); // A
        $this->enroll($raka, $pastIf102, 82, $sari); // AB
        $this->enroll($raka, $pastIf103, 75, $budi); // B
        $this->enroll($dewi, $pastIf101, 50, $budi); // D: not passed
        $this->enroll($dewi, $pastIf102, 68, $sari); // BC
        $this->enroll($dewi, $pastIf103, 72, $budi); // B

        // This semester: open for registration.
        $current = Semester::factory()->active()->create([
            'code' => '2026-1',
            'name' => 'Ganjil 2026/2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-01-31',
        ]);

        $if101A = $this->section($if101, $current, 'A', $budi, 30, [[1, '08:00', '10:30', 'lecture', 'R-101'], [3, '13:00', '15:00', 'lab', 'LAB-1']]);
        $if101B = $this->section($if101, $current, 'B', $sari, 30, [[2, '08:00', '10:30', 'lecture', 'R-102'], [4, '13:00', '15:00', 'lab', 'LAB-1']]);
        $if201A = $this->section($if201, $current, 'A', $budi, 30, [[1, '13:00', '15:30', 'lecture', 'R-201']]);
        $this->section($if201, $current, 'B', $sari, 30, [[3, '08:00', '10:30', 'lecture', 'R-202']]);
        $if202A = $this->section($if202, $current, 'A', $sari, 30, [[1, '09:00', '11:30', 'lecture', 'R-203']]);
        $if203A = $this->section($if203, $current, 'A', $budi, 30, [[2, '13:00', '15:30', 'lecture', 'R-204'], [5, '13:00', '15:00', 'lab', 'LAB-2']]);
        $if204A = $this->section($if204, $current, 'A', $sari, 2, [[4, '08:00', '10:30', 'lecture', 'LAB-2']]);
        $this->section($if205, $current, 'A', $budi, 30, [[5, '08:00', '10:30', 'lecture', 'R-205']]);
        $this->section($if301, $current, 'A', $sari, 30, [[5, '08:00', '10:30', 'lecture', 'R-301']]);

        $this->enroll($raka, $if201A);
        $this->enroll($raka, $if203A);
        $this->enroll($dewi, $if101B); // retaking the course she failed
        $this->enroll($dewi, $if202A);
        $this->enroll($andi, $if101A);
        $this->enroll($putri, $if101A);
        $this->enroll($putri, $if204A);
        $this->enroll($fajar, $if101B);
        $this->enroll($fajar, $if204A); // IF204 A is now full (2 / 2)
    }

    /**
     * @param  list<array{int, string, string, string, string}>  $schedules  [day (1 = Monday), start, end, meeting type, room]
     */
    private function section(Course $course, Semester $semester, string $code, User $lecturer, int $capacity, array $schedules): CourseSection
    {
        $section = CourseSection::factory()->create([
            'course_id' => $course->id,
            'semester_id' => $semester->id,
            'lecturer_id' => $lecturer->id,
            'section_code' => $code,
            'capacity' => $capacity,
        ]);

        foreach ($schedules as [$day, $start, $end, $meetingType, $room]) {
            $section->schedules()->create([
                'day_of_week' => $day,
                'start_time' => $start,
                'end_time' => $end,
                'meeting_type' => $meetingType,
                'room' => $room,
            ]);
        }

        return $section;
    }

    /**
     * Enroll a student directly, and grade the enrollment when a score is given.
     */
    private function enroll(User $student, CourseSection $section, ?float $score = null, ?User $grader = null): void
    {
        $enrollment = Enrollment::factory()->create([
            'student_id' => $student->id,
            'course_section_id' => $section->id,
            'status' => EnrollmentStatus::Enrolled,
        ]);

        if ($score === null) {
            return;
        }

        $result = $this->gradeScale->convert($score);

        Grade::factory()->create([
            'enrollment_id' => $enrollment->id,
            'score' => $score,
            'letter' => $result->letter,
            'grade_point' => $result->gradePoint,
            'graded_by' => $grader?->id ?? $section->lecturer_id,
            'graded_at' => $section->semester->end_date,
        ]);
    }
}
