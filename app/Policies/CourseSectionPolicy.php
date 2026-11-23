<?php

namespace App\Policies;

use App\Models\CourseSection;
use App\Models\User;

class CourseSectionPolicy
{
    /**
     * Only the section's own lecturer sees its student list (to grade them, R5).
     */
    public function viewRoster(User $user, CourseSection $section): bool
    {
        return $section->lecturer_id === $user->id;
    }
}
