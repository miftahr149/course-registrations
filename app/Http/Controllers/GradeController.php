<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Services\AcademicRecord;
use App\Services\GradeScale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Every action answers JSON to API clients and HTML (a page or a redirect) to the browser.
 */
class GradeController extends Controller
{
    public function __construct(
        private GradeScale $gradeScale,
        private AcademicRecord $academicRecord,
    ) {}

    /**
     * The section's lecturer enters (or corrects) the score of one enrollment.
     */
    public function update(Request $request, Enrollment $enrollment): JsonResponse|RedirectResponse
    {
        Gate::authorize('grade', $enrollment);

        $validated = $request->validate([
            'score' => ['required', 'numeric', 'between:0,100'],
        ]);

        if ($enrollment->status === EnrollmentStatus::Dropped) {
            $message = 'A dropped enrollment cannot be graded.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->with('error', $message);
        }

        $result = $this->gradeScale->convert((float) $validated['score']);

        $grade = Grade::query()->updateOrCreate(
            ['enrollment_id' => $enrollment->id],
            [
                'score' => $validated['score'],
                'letter' => $result->letter,
                'grade_point' => $result->gradePoint,
                'graded_by' => $request->user()->id,
                'graded_at' => now(),
            ],
        );

        return $request->expectsJson()
            ? response()->json(['data' => $grade])
            : back()->with('status', "Saved {$enrollment->student->name}'s grade: {$grade->letter}.");
    }

    /**
     * The logged-in student's own grades (KHS) and cumulative GPA.
     */
    public function index(Request $request): JsonResponse|View
    {
        $student = $request->user();

        $grades = Grade::query()
            ->whereHas('enrollment', fn ($query) => $query
                ->where('student_id', $student->id)
                ->where('status', EnrollmentStatus::Enrolled))
            ->with('enrollment.section.course', 'enrollment.section.semester')
            ->get();

        $gpa = $this->academicRecord->gpaFor($student);

        if ($request->expectsJson()) {
            return response()->json(['data' => $grades, 'gpa' => $gpa]);
        }

        return view('grades.index', [
            'gradesBySemester' => $grades
                ->sortByDesc(fn (Grade $grade) => $grade->enrollment->section->semester->start_date)
                ->groupBy(fn (Grade $grade): string => $grade->enrollment->section->semester->name),
            'gpa' => $gpa,
            'creditLimit' => $this->academicRecord->creditLimitFor($student),
        ]);
    }
}
