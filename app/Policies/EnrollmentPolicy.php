<?php

namespace App\Policies;

use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    /**
     * Only students register for sections.
     */
    public function create(User $user, CourseSection $section): bool
    {
        return $user->isStudent();
    }

    /**
     * A student may only see their own enrollment. The section's lecturer and admins may see all.
     */
    public function view(User $user, Enrollment $enrollment): bool
    {
        return $user->isAdmin()
            || $enrollment->student_id === $user->id
            || $enrollment->section->lecturer_id === $user->id;
    }

    /**
     * Only the student who enrolled may drop the enrollment.
     */
    public function delete(User $user, Enrollment $enrollment): bool
    {
        return $enrollment->student_id === $user->id;
    }

    /**
     * R5: only the lecturer who teaches the section may enter or change its grades.
     */
    public function grade(User $user, Enrollment $enrollment): bool
    {
        return $enrollment->section->lecturer_id === $user->id;
    }
}
