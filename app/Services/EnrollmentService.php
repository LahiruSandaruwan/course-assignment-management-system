<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class EnrollmentService
{
    /**
     * Enroll a student in a course.
     */
    public function enrollStudent(Course $course, User $student, User $actor): void
    {
        DB::transaction(function () use ($course, $student, $actor) {
            // Pre-check for duplicate (fast path for better UX)
            if ($course->students()->where('user_id', $student->id)->exists()) {
                throw new ConflictHttpException('Student is already enrolled in this course.');
            }

            try {
                $course->students()->attach($student->id);
            } catch (QueryException $e) {
                // Race-safe duplicate handling: Catch DB constraint violation.
                // SQLite throws 19, MySQL throws 23000 / 1062 for unique constraint violations.
                if ($e->getCode() == 23000 || $e->getCode() == 19) {
                    Log::info('Enrollment race detected, student already enrolled', [
                        'course_id' => $course->id,
                        'student_id' => $student->id,
                    ]);

                    throw new ConflictHttpException('Student is already enrolled in this course.');
                }

                throw $e;
            }

            Log::info('Student enrolled in course', [
                'course_id' => $course->id,
                'student_id' => $student->id,
                'actor_id' => $actor->id,
            ]);
        });
    }

    /**
     * Remove a student from a course.
     */
    public function removeStudent(Course $course, User $student, User $actor): void
    {
        DB::transaction(function () use ($course, $student, $actor) {
            $course->students()->detach($student->id);

            Log::info('Student removed from course', [
                'course_id' => $course->id,
                'student_id' => $student->id,
                'actor_id' => $actor->id,
            ]);
        });
    }
}
