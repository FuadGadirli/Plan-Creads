<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Şifrənin bərpası — '.Setting::get('agency_name').' Plan')
            ->greeting('Salam!')
            ->line('Hesabınız üçün şifrə bərpası tələb olundu.')
            ->action('Yeni şifrə təyin et', route('password.reset', ['token' => $this->token, 'email' => $notifiable->email]))
            ->line('Əgər bu tələbi siz etməmisinizsə, bu maili nəzərə almayın.')
            ->salutation(Setting::get('agency_name').' Plan');
    }
}
