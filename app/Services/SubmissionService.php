<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use App\Enums\SubmissionStatus;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class SubmissionService
{
    /**
     * Submit an assignment, preventing races on upsert and blocking graded resubmissions.
     *
     * @param Assignment $assignment
     * @param User $student
     * @param string $text
     * @return Submission
     * @throws UnprocessableEntityHttpException if the submission has already been graded
     * @throws QueryException on an unexpected (non-duplicate) database error
     */
    public function submit(Assignment $assignment, User $student, string $text): Submission
    {
        return DB::transaction(function () use ($assignment, $student, $text) {
            // Check if there is an existing submission
            $existing = Submission::where('assignment_id', $assignment->id)
                ->where('student_id', $student->id)
                ->first();

            if ($existing) {
                if ($existing->status === SubmissionStatus::Graded) {
                    throw new UnprocessableEntityHttpException('Cannot resubmit an assignment that has already been graded.');
                }

                $existing->update([
                    'submission_text' => $text,
                    'submitted_at' => now(),
                ]);

                return $existing;
            }

            // Try to insert
            try {
                return $assignment->submissions()->create([
                    'student_id' => $student->id,
                    'submission_text' => $text,
                    'submitted_at' => now(),
                    'status' => SubmissionStatus::Submitted->value,
                ]);
            } catch (QueryException $e) {
                // 23000 is the SQLSTATE for integrity constraint violation (e.g. duplicate key)
                if ($e->getCode() == '23000') {
                    Log::info('Submission race detected, updating existing row instead', [
                        'assignment_id' => $assignment->id,
                        'student_id' => $student->id,
                    ]);

                    // Raced with another insert. The row exists now, fetch and update it.
                    $raced = Submission::where('assignment_id', $assignment->id)
                        ->where('student_id', $student->id)
                        ->first();

                    if ($raced && $raced->status === SubmissionStatus::Graded) {
                        throw new UnprocessableEntityHttpException('Cannot resubmit an assignment that has already been graded.');
                    }

                    if ($raced) {
                        $raced->update([
                            'submission_text' => $text,
                            'submitted_at' => now(),
                        ]);
                        return $raced;
                    }
                }

                throw $e;
            }
        });
    }
}
