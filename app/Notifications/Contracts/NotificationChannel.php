<?php

namespace App\Notifications\Contracts;

use App\Models\Submission;

interface NotificationChannel
{
    /**
     * Send a notification for a submission.
     *
     * @param Submission $submission
     * @return void
     */
    public function send(Submission $submission): void;
}
