<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;
use App\Models\Assignment;
use Illuminate\Auth\Access\Response;

class SubmissionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Assignment $assignment): bool
    {
        if ($user->role === \App\Enums\Role::Admin) {
            return true;
        }

        if ($user->role === \App\Enums\Role::Instructor) {
            return $user->id === $assignment->course->instructor_id;
        }

        // Students cannot view a list of all submissions for an assignment
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Submission $submission): bool
    {
        if ($user->role === \App\Enums\Role::Admin) {
            return true;
        }

        if ($user->role === \App\Enums\Role::Instructor) {
            return $user->id === $submission->assignment->course->instructor_id;
        }

        // Student can only view their own submission
        if ($user->role === \App\Enums\Role::Student) {
            return $user->id === $submission->student_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Assignment $assignment): bool
    {
        if ($user->role === \App\Enums\Role::Student) {
            // Must be published
            if ($assignment->status !== \App\Enums\AssignmentStatus::Published) {
                return false;
            }
            // Must be enrolled
            return $assignment->course->students()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can grade the submission.
     */
    public function grade(User $user, Submission $submission): bool
    {
        if ($user->role === \App\Enums\Role::Admin) {
            return true;
        }

        if ($user->role === \App\Enums\Role::Instructor) {
            return $user->id === $submission->assignment->course->instructor_id;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Submission $submission): bool
    {
        return false;
    }
}
