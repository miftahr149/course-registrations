<?php

namespace App\Http\Controllers;

use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Services\EnrollmentRejected;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EnrollmentController extends Controller
{
    public function __construct(private EnrollmentService $enrollmentService) {}

    /**
     * The logged-in student's current enrollments (their KRS).
     */
    public function index(Request $request): JsonResponse
    {
        $enrollments = $request->user()->enrollments()
            ->with('section.course', 'section.schedules')
            ->latest()
            ->get();

        return response()->json(['data' => $enrollments]);
    }

    public function store(Request $request, CourseSection $section): JsonResponse
    {
        Gate::authorize('create', [Enrollment::class, $section]);

        try {
            $enrollment = $this->enrollmentService->enroll($request->user(), $section);
        } catch (EnrollmentRejected $rejection) {
            return response()->json(['message' => $rejection->getMessage()], 422);
        }

        return response()->json(['data' => $enrollment], 201);
    }

    public function show(Enrollment $enrollment): JsonResponse
    {
        Gate::authorize('view', $enrollment);

        return response()->json(['data' => $enrollment->load('section.course', 'grade')]);
    }

    public function destroy(Enrollment $enrollment): JsonResponse
    {
        Gate::authorize('delete', $enrollment);

        $this->enrollmentService->drop($enrollment);

        return response()->json(['data' => $enrollment]);
    }
}
