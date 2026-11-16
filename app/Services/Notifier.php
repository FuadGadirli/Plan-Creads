<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\PlatformNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class Notifier
{
    /**
     * Platforma daxili bildiriş + mail göndərir.
     * Hərəkəti edən istifadəçinin özünə bildiriş getmir.
     */
    public static function send(iterable $users, string $title, string $body, string $url, ?string $brand = null): void
    {
        $users = Collection::make($users)
            ->filter(fn ($u) => $u instanceof User && $u->is_active)
            ->reject(fn (User $u) => $u->id === auth()->id())
            ->unique('id');

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new PlatformNotification($title, $body, $url, $brand));
    }
}
