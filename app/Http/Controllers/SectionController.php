<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Models\CourseSection;
use Illuminate\Http\JsonResponse;

class SectionController extends Controller
{
    /**
     * Sections open for registration in the active semester, with the seats still free.
     */
    public function index(): JsonResponse
    {
        $sections = CourseSection::query()
            ->whereHas('semester', fn ($query) => $query->where('is_active', true))
            ->with('course', 'lecturer:id,name', 'schedules')
            ->withCount(['enrollments as enrolled_count' => fn ($query) => $query->where('status', EnrollmentStatus::Enrolled)])
            ->orderBy('course_id')
            ->orderBy('section_code')
            ->get()
            ->map(fn (CourseSection $section): array => [
                ...$section->toArray(),
                'seats_left' => max(0, $section->capacity - (int) $section->getAttribute('enrolled_count')),
            ]);

        return response()->json(['data' => $sections]);
    }
}
