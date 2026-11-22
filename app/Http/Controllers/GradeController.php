<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Services\AcademicRecord;
use App\Services\GradeScale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GradeController extends Controller
{
    public function __construct(
        private GradeScale $gradeScale,
        private AcademicRecord $academicRecord,
    ) {}

    /**
     * The section's lecturer enters (or corrects) the score of one enrollment.
     */
    public function update(Request $request, Enrollment $enrollment): JsonResponse
    {
        Gate::authorize('grade', $enrollment);

        $validated = $request->validate([
            'score' => ['required', 'numeric', 'between:0,100'],
        ]);

        if ($enrollment->status === EnrollmentStatus::Dropped) {
            return response()->json(['message' => 'A dropped enrollment cannot be graded.'], 422);
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

        return response()->json(['data' => $grade]);
    }

    /**
     * The logged-in student's own grades (KHS) and cumulative GPA.
     */
    public function index(Request $request): JsonResponse
    {
        $student = $request->user();

        $grades = Grade::query()
            ->whereHas('enrollment', fn ($query) => $query
                ->where('student_id', $student->id)
                ->where('status', EnrollmentStatus::Enrolled))
            ->with('enrollment.section.course')
            ->get();

        return response()->json([
            'data' => $grades,
            'gpa' => $this->academicRecord->gpaFor($student),
        ]);
    }
}
