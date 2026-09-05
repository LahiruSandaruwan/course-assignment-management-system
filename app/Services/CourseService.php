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
    public function getCoursesForUser(User $user)
    {
        $query = Course::with('instructor:id,name')
            ->withCount(['students', 'assignments']);

        if ($user->role === Role::Instructor) {
            $query->where('instructor_id', $user->id);
        } elseif ($user->role === Role::Student) {
            // A student's list must match what CoursePolicy::view already
            // enforces per-course: enrolled AND active. Filtering by status
            // alone previously leaked every active course to every student,
            // regardless of enrollment.
            $query->where('status', CourseStatus::Active->value)
                ->whereHas('students', fn ($q) => $q->where('users.id', $user->id));
        }

        return $query->paginate(15);
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
