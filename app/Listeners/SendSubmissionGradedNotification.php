<?php

namespace App\Listeners;

use App\Events\SubmissionGraded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Implements ShouldQueueAfterCommit so the queued job is only dispatched
 * once GradingService's transaction commits — a rolled-back grade can
 * never trigger a notification.
 */
class SendSubmissionGradedNotification implements ShouldQueue, ShouldQueueAfterCommit
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SubmissionGraded $event): void
    {
        // In a real app, you could inject an array of channels, or resolve them from the container
        $channel = app(\App\Notifications\Contracts\NotificationChannel::class);
        $channel->send($event->submission);
    }
}
