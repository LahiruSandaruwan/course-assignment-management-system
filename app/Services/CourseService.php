<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use App\Enums\Role;
use App\Enums\CourseStatus;
use Illuminate\Database\Eloquent\Collection;

class CourseService
{
    /**
     * Get courses accessible by the given user.
     */
    public function getCoursesForUser(User $user): Collection
    {
        if ($user->role === Role::Admin) {
            return Course::with('instructor')->get();
        }

        if ($user->role === Role::Instructor) {
            return Course::with('instructor')
                ->where('instructor_id', $user->id)
                ->get();
        }

        // Students can only see active courses
        // Note: they might also only see enrolled courses depending on requirements,
        // but typically a student can browse active courses to enroll.
        return Course::with('instructor')
            ->where('status', CourseStatus::Active->value)
            ->get();
    }

    /**
     * Create a new course.
     */
    public function createCourse(array $data, User $user): Course
    {
        if ($user->role === Role::Instructor) {
            $data['instructor_id'] = $user->id;
        } elseif (empty($data['instructor_id'])) {
            $data['instructor_id'] = $user->id; // Admin default to self if not specified
        }

        return Course::create($data);
    }

    /**
     * Update an existing course.
     */
    public function updateCourse(Course $course, array $data): bool
    {
        return $course->update($data);
    }

    /**
     * Delete a course.
     */
    public function deleteCourse(Course $course): ?bool
    {
        return $course->delete();
    }
}
