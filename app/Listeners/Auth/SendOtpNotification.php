<?php

namespace App\Listeners\Auth;

use App\Events\Auth\OtpRequested;
use App\Notifications\Auth\OtpNotification;
use App\Sms\Interfaces\SmsDriverInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendOtpNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(private readonly SmsDriverInterface $smsDriver)
    {}

    /**
     * Handle the event.
     */
    public function handle(OtpRequested $event): void
    {
        Log::info("📣 Listener caught event for: {$event->identifier}");

        if ($event->type === 'mobile') {
            Notification::route('sms', $event->identifier)
                ->notify(new OtpNotification($event->code));
        } else {
            Notification::route('mail', $event->identifier)
                ->notify(new OtpNotification($event->code));
        }
    }
}
