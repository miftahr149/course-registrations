<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\SectionSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * R3, R4 and R6: decides whether a student may enroll in a section.
 */
final class EnrollmentService
{
    public function __construct(private AcademicRecord $academicRecord) {}

    /**
     * @throws EnrollmentRejected
     */
    public function enroll(User $student, CourseSection $section): Enrollment
    {
        return DB::transaction(function () use ($student, $section): Enrollment {
            // Lock the section row so two students cannot take the last seat at the same time.
            $section = CourseSection::query()->with(['course', 'semester', 'schedules'])->lockForUpdate()->findOrFail($section->id);

            $this->ensureSemesterIsOpen($section);
            $this->ensureNotAlreadyEnrolled($student, $section);
            $this->ensureNoOtherSectionOfSameCourse($student, $section);
            $this->ensureSeatIsAvailable($section);
            $this->ensurePrerequisiteIsPassed($student, $section);
            $this->ensureNoScheduleClash($student, $section);
            $this->ensureCreditLimitIsRespected($student, $section);

            // A dropped row is reused, because (student, section) is UNIQUE in the database.
            $enrollment = Enrollment::query()->firstOrNew([
                'student_id' => $student->id,
                'course_section_id' => $section->id,
            ]);
            $enrollment->status = EnrollmentStatus::Enrolled;
            $enrollment->save();

            return $enrollment;
        });
    }

    public function drop(Enrollment $enrollment): void
    {
        $enrollment->update(['status' => EnrollmentStatus::Dropped]);
    }

    private function ensureSemesterIsOpen(CourseSection $section): void
    {
        if (! $section->semester->is_active) {
            throw new EnrollmentRejected('Registration for this semester is closed.');
        }
    }

    private function ensureNotAlreadyEnrolled(User $student, CourseSection $section): void
    {
        $isEnrolled = $this->activeEnrollments($student)
            ->where('course_section_id', $section->id)
            ->exists();

        if ($isEnrolled) {
            throw new EnrollmentRejected('You are already enrolled in this section.');
        }
    }

    private function ensureNoOtherSectionOfSameCourse(User $student, CourseSection $section): void
    {
        $hasOtherSection = $this->activeEnrollments($student)
            ->whereHas('section', fn ($query) => $query
                ->where('course_id', $section->course_id)
                ->where('semester_id', $section->semester_id))
            ->exists();

        if ($hasOtherSection) {
            throw new EnrollmentRejected("You are already enrolled in another section of {$section->course->code}.");
        }
    }

    private function ensureSeatIsAvailable(CourseSection $section): void
    {
        if ($section->enrolledCount() >= $section->capacity) {
            throw new EnrollmentRejected('This section is full.');
        }
    }

    private function ensurePrerequisiteIsPassed(User $student, CourseSection $section): void
    {
        $prerequisiteId = $section->course->prerequisite_course_id;

        if ($prerequisiteId !== null && ! $this->academicRecord->hasPassed($student, $prerequisiteId)) {
            throw new EnrollmentRejected('You have not passed the prerequisite course.');
        }
    }

    private function ensureNoScheduleClash(User $student, CourseSection $section): void
    {
        $newSlots = $this->slotsOf($section->schedules);
        $currentSlots = $this->slotsOf(SectionSchedule::query()
            ->whereIn('course_section_id', $this->currentSemesterEnrollments($student, $section)->select('course_section_id'))
            ->get());

        foreach ($newSlots as $newSlot) {
            foreach ($currentSlots as $currentSlot) {
                if ($newSlot->overlaps($currentSlot)) {
                    throw new EnrollmentRejected('This section clashes with your schedule.');
                }
            }
        }
    }

    private function ensureCreditLimitIsRespected(User $student, CourseSection $section): void
    {
        $currentCredits = (int) $this->currentSemesterEnrollments($student, $section)
            ->join('course_sections', 'course_sections.id', '=', 'enrollments.course_section_id')
            ->join('courses', 'courses.id', '=', 'course_sections.course_id')
            ->sum('courses.credits');
        $limit = $this->academicRecord->creditLimitFor($student);

        if ($currentCredits + $section->course->credits > $limit) {
            throw new EnrollmentRejected("This section would exceed your credit limit of {$limit} SKS.");
        }
    }

    /**
     * @return Builder<Enrollment>
     */
    private function activeEnrollments(User $student): Builder
    {
        return Enrollment::query()
            ->where('enrollments.student_id', $student->id)
            ->where('enrollments.status', EnrollmentStatus::Enrolled);
    }

    /**
     * @return Builder<Enrollment>
     */
    private function currentSemesterEnrollments(User $student, CourseSection $section): Builder
    {
        return $this->activeEnrollments($student)
            ->whereHas('section', fn ($query) => $query->where('semester_id', $section->semester_id));
    }

    /**
     * @param  Collection<int, SectionSchedule>  $schedules
     * @return Collection<int, TimeSlot>
     */
    private function slotsOf(Collection $schedules): Collection
    {
        return $schedules->map(fn (SectionSchedule $schedule): TimeSlot => new TimeSlot(
            $schedule->day_of_week,
            $schedule->start_time,
            $schedule->end_time,
        ));
    }
}
