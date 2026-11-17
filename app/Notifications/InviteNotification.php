<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InviteNotification extends Notification
{
    public function __construct(public string $token, public string $inviter) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $agency = Setting::get('agency_name');
        $url = route('password.reset', ['token' => $this->token, 'email' => $notifiable->email]);

        return (new MailMessage)
            ->subject("{$agency} Plan platformasına dəvət")
            ->greeting('Salam, '.$notifiable->name.'!')
            ->line("{$this->inviter} sizi {$agency} Plan platformasına əlavə etdi.")
            ->line('Hesabınızı aktivləşdirmək üçün aşağıdakı düymə ilə şifrənizi təyin edin.')
            ->action('Şifrəni təyin et', $url)
            ->line('Giriş üçün e-poçtunuz: '.$notifiable->email)
            ->line('Link 7 gün etibarlıdır.')
            ->salutation("{$agency} Plan");
    }
}
