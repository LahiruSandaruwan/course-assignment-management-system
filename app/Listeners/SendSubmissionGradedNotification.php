<?php

namespace App\Listeners;

use App\Events\SubmissionGraded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendSubmissionGradedNotification
{
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
