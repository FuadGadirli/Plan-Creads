<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlatformNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $path;

    public function __construct(
        public string $title,
        public string $body,
        string $url,
        public ?string $brand = null,
    ) {
        // Nisbi yol saxlanılır ki, domen dəyişəndə (creads.az → 19mbt.com) linklər işləsin
        $p = parse_url($url);
        $this->path = ($p['path'] ?? '/').(isset($p['query']) ? '?'.$p['query'] : '').(isset($p['fragment']) ? '#'.$p['fragment'] : '');
    }

    public function via(object $notifiable): array
    {
        return $notifiable->email_notifications ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = ($this->brand ? "[{$this->brand}] " : '').$this->title;

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Salam, '.$notifiable->name.'!')
            ->line('**'.$this->title.'**');
        foreach (preg_split('/\n+/', $this->body) as $line) {
            $mail->line($line);
        }

        return $mail->action('Platformada aç', url($this->path))
            ->salutation(Setting::get('agency_name').' Plan · Bu avtomatik bildirişdir, cavab yazmayın.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->path,
            'brand' => $this->brand,
        ];
    }
}
