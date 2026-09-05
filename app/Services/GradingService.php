<?php

namespace App\Services;

use App\Models\Submission;
use App\Enums\SubmissionStatus;
use App\Events\SubmissionGraded;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GradingService
{
    /**
     * Grade a submission with pessimistic locking to prevent concurrency issues.
     *
     * @param Submission $submission
     * @param int $score
     * @param string|null $feedback
     * @return Submission
     * @throws ValidationException
     */
    public function grade(Submission $submission, int $score, ?string $feedback): Submission
    {
        return DB::transaction(function () use ($submission, $score, $feedback) {
            // Lock the row for update to prevent concurrent grading
            $lockedSubmission = Submission::where('id', $submission->id)->lockForUpdate()->firstOrFail();

            // Validate the score against the assignment's max score inside the lock
            // to ensure the assignment itself hasn't changed (though assignment max_score shouldn't change generally,
            // we do this for correctness)
            $maxScore = $lockedSubmission->assignment->max_score;

            if ($score < 0 || $score > $maxScore) {
                throw ValidationException::withMessages([
                    'score' => ["The score must be between 0 and {$maxScore}."],
                ]);
            }

            $lockedSubmission->update([
                'score' => $score,
                'instructor_feedback' => $feedback,
                'status' => SubmissionStatus::Graded->value,
            ]);

            // Dispatch event which triggers notifications
            event(new SubmissionGraded($lockedSubmission));

            return $lockedSubmission;
        });
    }
}
