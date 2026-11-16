<?php

namespace App\Console\Commands;

use App\Models\ContentPlan;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Services\Notifier;
use App\Support\Labels;
use Illuminate\Console\Command;

/**
 * Müştəri təsdiqi gözləyən paylaşımlar üçün avtomatik xatırlatmalar.
 * Hostinger cron hər dəqiqə "schedule:run" işlədir, bu əmr isə saatda bir dəfə çağırılır.
 */
class SendReminders extends Command
{
    protected $signature = 'plan:reminders {--force : İş saatı və interval yoxlamasını keç}';

    protected $description = 'Müştəriyə təsdiq xatırlatmaları və komandaya gündəlik paylaşım bildirişi göndərir';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $hour = (int) now()->format('G');
        $start = (int) Setting::get('reminder_start_hour');
        $end = (int) Setting::get('reminder_end_hour');

        if (! $force && ($hour < $start || $hour >= $end)) {
            $this->info('İş saatı xaricindədir.');

            return self::SUCCESS;
        }

        if ($force || $hour === $start) {
            $this->morningDigest();
        }

        if (Setting::get('reminder_enabled') !== '1' && ! $force) {
            return self::SUCCESS;
        }

        $interval = max(1, (int) Setting::get('reminder_interval_hours'));
        $maxPerDay = max(1, (int) Setting::get('reminder_max_per_day'));
        $daysBefore = (int) Setting::get('reminder_days_before');

        $posts = Post::with('brand')->where('status', 'sent')
            ->whereDate('publish_date', '<=', today()->addDays($daysBefore))
            ->where(function ($q) use ($interval, $force) {
                $q->whereNull('last_reminder_at');
                if (! $force) {
                    $q->orWhere('last_reminder_at', '<=', now()->subHours($interval)->addMinutes(5));
                } else {
                    $q->orWhereNotNull('last_reminder_at');
                }
            })
            ->orderBy('publish_date')->get()
            // Gündəlik limit: bu gün artıq neçə dəfə xatırladılıb
            ->filter(fn (Post $p) => ! $p->last_reminder_at?->isToday() || $p->reminder_count < $maxPerDay);

        foreach ($posts->groupBy('brand_id') as $brandPosts) {
            $brand = $brandPosts->first()->brand;
            $clients = User::clientUsersOf($brand);
            if ($clients->isEmpty()) {
                continue;
            }
            $today = $brandPosts->filter(fn ($p) => $p->publish_date->isToday() || $p->publish_date->isPast());
            $lines = $brandPosts->map(fn ($p) => '• '.Labels::shortDate($p->publish_date).' — '.$p->title.($p->publish_date->isToday() ? ' (BU GÜN)' : ''))->implode("\n");
            $title = $today->isNotEmpty()
                ? "Təcili: bu gün paylaşılacaq {$today->count()} paylaşım təsdiqinizi gözləyir"
                : "{$brandPosts->count()} paylaşım təsdiqinizi gözləyir";

            Notifier::send($clients, $title, $lines, route('portal.posts', ['view' => 'list']), $brand->name);

            foreach ($brandPosts as $p) {
                $p->update([
                    'reminder_count' => $p->last_reminder_at?->isToday() ? $p->reminder_count + 1 : 1,
                    'last_reminder_at' => now(),
                ]);
            }
            $this->info("{$brand->name}: {$brandPosts->count()} paylaşım üçün xatırlatma göndərildi.");
        }

        // Kontent planlar: gündə bir dəfə (səhər)
        if ($force || $hour === $start) {
            foreach (ContentPlan::with('brand')->where('status', 'sent')->get() as $plan) {
                Notifier::send(User::clientUsersOf($plan->brand), "Xatırlatma: {$plan->title()} kontent planı təsdiqinizi gözləyir",
                    'Çəkiliş və dizayn işlərinə başlamaq üçün təsdiqiniz lazımdır.', route('portal.content.show', $plan), $plan->brand->name);
            }
        }

        return self::SUCCESS;
    }

    /** Səhər komandaya: bu gün paylaşılacaq postlar */
    private function morningDigest(): void
    {
        $posts = Post::with('brand')->whereDate('publish_date', today())
            ->whereIn('status', ['client_approved', 'sent', 'pending', 'draft', 'internal_changes', 'client_changes'])->get();
        if ($posts->isEmpty()) {
            return;
        }
        $lines = $posts->map(fn ($p) => '• '.$p->brand->name.' — '.$p->title.' ('.Labels::label(Labels::APPROVAL_STATUSES, $p->status).')')->implode("\n");
        $recipients = User::approvers()->merge($posts->map->creator->filter());
        Notifier::send($recipients, "Bu gün {$posts->count()} paylaşım planlaşdırılıb", $lines, route('dashboard'));
    }
}
