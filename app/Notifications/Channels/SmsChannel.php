<?php

namespace App\Notifications\Channels;

use App\Sms\Exceptions\SmsSendingFailedException;
use App\Sms\Interfaces\SmsDriverInterface;
use Illuminate\Notifications\Notification;
use Exception;

readonly class SmsChannel
{
    public function __construct(private SmsDriverInterface $smsDriver)
    {
    }

    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param Notification $notification
     * @return void
     * @throws SmsSendingFailedException
     * @throws Exception
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toSms')) {
            throw new Exception("Notification must have 'toSms' method for SmsChannel.");
        }

        $mobile = $notifiable->routeNotificationFor('sms') ?? $notifiable->mobile;
        if (!$mobile) return;

        $payload = $notification->toSms($notifiable);

        if (! empty($payload['pattern'])) {
            $this->smsDriver->sendPattern(
                mobile: $mobile,
                patternCode: $payload['pattern'],
                params: $payload['data'] ?? [],
            );
            return;
        }

        if (! empty($payload['message'])) {
            $this->smsDriver->send(
                mobile: $mobile,
                message: $payload['message'],
            );
            return;
        }

        throw new Exception("Invalid SMS payload provided. Provide either 'pattern' or 'message'.");
    }
}
