<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Grade;
use App\Models\User;

/**
 * Reads a student's grades from the database and applies the pure grading rules.
 * The GPA is computed on every call and never stored, so it cannot go out of sync.
 */
final class AcademicRecord
{
    public function __construct(
        private GpaCalculator $gpaCalculator,
        private CreditLimit $creditLimit,
    ) {}

    /**
     * Cumulative GPA over every graded, non-dropped enrollment. Null when nothing is graded yet.
     */
    public function gpaFor(User $student): ?float
    {
        $results = Grade::query()
            ->join('enrollments', 'enrollments.id', '=', 'grades.enrollment_id')
            ->join('course_sections', 'course_sections.id', '=', 'enrollments.course_section_id')
            ->join('courses', 'courses.id', '=', 'course_sections.course_id')
            ->where('enrollments.student_id', $student->id)
            ->where('enrollments.status', EnrollmentStatus::Enrolled)
            ->get(['grades.grade_point', 'courses.credits'])
            ->map(fn (Grade $grade): array => [
                'grade_point' => (float) $grade->grade_point,
                'credits' => (int) $grade->getAttribute('credits'),
            ]);

        return $this->gpaCalculator->calculate($results);
    }

    public function creditLimitFor(User $student): int
    {
        return $this->creditLimit->forGpa($this->gpaFor($student));
    }

    /**
     * Whether the student has a passing grade (C or better) in any section of the course.
     */
    public function hasPassed(User $student, int $courseId): bool
    {
        return Grade::query()
            ->whereHas('enrollment', fn ($enrollment) => $enrollment
                ->where('student_id', $student->id)
                ->where('status', EnrollmentStatus::Enrolled)
                ->whereHas('section', fn ($section) => $section->where('course_id', $courseId)))
            ->where('grade_point', '>=', GradeScale::PASSING_GRADE_POINT)
            ->exists();
    }

    /**
     * A one-line summary for the student dashboard, e.g. "GPA 3.60 (max 24 SKS)".
     */
    public function summaryFor(User $student): string
    {
        $gpa = $this->gpaFor($student);

        return sprintf('GPA %s (max %d SKS)', $this->gpaCalculator->format($gpa), $this->creditLimitFor($student));
    }
}
