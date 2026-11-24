<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Semester;
use App\Services\AcademicRecord;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SectionController extends Controller
{
    public function __construct(private AcademicRecord $academicRecord) {}

    /**
     * The sections open for registration in the current semester.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $semester = Semester::current();

        $sections = $semester === null ? collect() : $semester->sections()
            ->with('course.prerequisite', 'lecturer', 'schedules')
            ->withCount(['enrollments as enrolled_count' => fn ($query) => $query->where('status', EnrollmentStatus::Enrolled)])
            ->get()
            ->sortBy(fn (CourseSection $section): array => [$section->course->code, $section->section_code])
            ->values();

        $myEnrollments = $semester !== null && $user->isStudent()
            ? $user->activeEnrollmentsIn($semester)->with('section.course')->get()
            : collect();

        return view('sections.index', [
            'semester' => $semester,
            'sections' => $sections,
            'enrolledSectionIds' => $myEnrollments->pluck('course_section_id')->all(),
            'creditsTaken' => $myEnrollments->sum(fn (Enrollment $enrollment): int => $enrollment->section->course->credits),
            'creditLimit' => $user->isStudent() ? $this->academicRecord->creditLimitFor($user) : null,
        ]);
    }
}
