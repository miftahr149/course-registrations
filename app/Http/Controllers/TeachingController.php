<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Models\CourseSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * The lecturer's side: the sections they teach and the students to grade.
 */
class TeachingController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isLecturer(), 403);

        $sections = $request->user()->taughtSections()
            ->with('course', 'semester', 'schedules')
            ->withCount(['enrollments as enrolled_count' => fn ($query) => $query->where('status', EnrollmentStatus::Enrolled)])
            ->get()
            ->sortBy([
                fn (CourseSection $a, CourseSection $b) => $b->semester->start_date <=> $a->semester->start_date,
                fn (CourseSection $a, CourseSection $b) => [$a->course->code, $a->section_code] <=> [$b->course->code, $b->section_code],
            ]);

        return view('teaching.index', [
            'sectionsBySemester' => $sections->groupBy(fn (CourseSection $section): string => $section->semester->name),
        ]);
    }

    /**
     * The section's students, each with a form to enter or correct the score.
     */
    public function show(CourseSection $section): View
    {
        Gate::authorize('viewRoster', $section);

        $enrollments = $section->enrollments()
            ->where('status', EnrollmentStatus::Enrolled)
            ->with('student', 'grade')
            ->get()
            ->sortBy(fn ($enrollment) => $enrollment->student->name);

        return view('teaching.show', [
            'section' => $section->load('course', 'semester', 'schedules'),
            'enrollments' => $enrollments,
        ]);
    }
}
