<?php

namespace App\Http\Controllers;

use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Semester;
use App\Services\AcademicRecord;
use App\Services\EnrollmentRejected;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Every action answers JSON to API clients and HTML (a page or a redirect) to the browser.
 */
class EnrollmentController extends Controller
{
    public function __construct(
        private EnrollmentService $enrollmentService,
        private AcademicRecord $academicRecord,
    ) {}

    /**
     * The logged-in student's current enrollments (their KRS).
     */
    public function index(Request $request): JsonResponse|View
    {
        $student = $request->user();

        if ($request->expectsJson()) {
            $enrollments = $student->enrollments()
                ->with('section.course', 'section.schedules')
                ->latest()
                ->get();

            return response()->json(['data' => $enrollments]);
        }

        $semester = Semester::current();
        $enrollments = $semester === null ? collect() : $student->activeEnrollmentsIn($semester)
            ->with('section.course', 'section.lecturer', 'section.schedules')
            ->get();

        return view('enrollments.index', [
            'semester' => $semester,
            'enrollments' => $enrollments,
            'creditsTaken' => $enrollments->sum(fn (Enrollment $enrollment): int => $enrollment->section->course->credits),
            'creditLimit' => $this->academicRecord->creditLimitFor($student),
        ]);
    }

    public function store(Request $request, CourseSection $section): JsonResponse|RedirectResponse
    {
        Gate::authorize('create', [Enrollment::class, $section]);

        try {
            $enrollment = $this->enrollmentService->enroll($request->user(), $section);
        } catch (EnrollmentRejected $rejection) {
            return $request->expectsJson()
                ? response()->json(['message' => $rejection->getMessage()], 422)
                : back()->with('error', $rejection->getMessage());
        }

        return $request->expectsJson()
            ? response()->json(['data' => $enrollment], 201)
            : back()->with('status', "Enrolled in {$section->course->code} section {$section->section_code}.");
    }

    public function show(Enrollment $enrollment): JsonResponse
    {
        Gate::authorize('view', $enrollment);

        return response()->json(['data' => $enrollment->load('section.course', 'grade')]);
    }

    public function destroy(Request $request, Enrollment $enrollment): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $enrollment);

        $this->enrollmentService->drop($enrollment);

        return $request->expectsJson()
            ? response()->json(['data' => $enrollment])
            : back()->with('status', "Dropped {$enrollment->section->course->code} section {$enrollment->section->section_code}.");
    }
}
