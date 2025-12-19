<?php

namespace App\Notifications\Auth;

use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public string $code)
    {
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $routes = $notifiable->routes ?? [];

        if (array_key_exists('sms', $routes)) {
            return [SmsChannel::class];
        }

        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the representation of the notification for the SMS channel.
     *
     * @return array{
     *  mobile: string,
     *  pattern: string,
     *  data: array<string, mixed>
     * }
     */
    public function toSms(object $notifiable): array
    {
        return [
            'pattern' => 'otp-login-pattern',
            'data' => [
                'code' => $this->code,
                'brand' => config('app.name'),
            ],
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
