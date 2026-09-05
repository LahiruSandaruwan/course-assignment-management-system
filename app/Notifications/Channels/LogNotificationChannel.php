<?php

namespace App\Notifications\Channels;

use App\Models\Submission;
use App\Notifications\Contracts\NotificationChannel;
use Illuminate\Support\Facades\Log;

class LogNotificationChannel implements NotificationChannel
{
    public function send(Submission $submission): void
    {
        Log::info("Submission {$submission->id} has been graded with a score of {$submission->score}.");
    }
}
