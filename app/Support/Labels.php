<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Platformadakı bütün statusların Azərbaycan dilində adları və rəngləri.
 */
class Labels
{
    public const ROLES = [
        'admin' => 'Rəhbərlik',
        'employee' => 'İşçi',
        'client' => 'Müştəri',
    ];

    public const PERMISSIONS = [
        'tasks.all' => ['Bütün tapşırıqları görmək', 'Yalnız özünə aid olanları yox, agentliyin bütün tapşırıqlarını görür'],
        'tasks.create' => ['Tapşırıq vermək', 'Yeni tapşırıq yarada və işçiləri təyin edə bilər'],
        'brands' => ['Markalar', 'Markalar bölməsinə daxil ola bilər'],
        'brands.manage' => ['Marka yaratmaq və redaktə', 'Yeni marka aça, məlumatlarını dəyişə bilər'],
        'brands.files' => ['Fayllar', 'Markaların fayl bölməsini görür və fayl yükləyir'],
        'brands.social' => ['Sosial media plan', 'Paylaşım təqvimini görür, paylaşım əlavə edir'],
        'brands.content' => ['Kontent plan', 'Aylıq kontent planları görür və yükləyir'],
        'approve' => ['Təsdiq hüququ', 'Kontent plan və paylaşımları təsdiqləyib müştəriyə göndərə bilər'],
        'crm' => ['Əməkdaşlıq', 'Müştərilər (CRM) bölməsini görür'],
        'team' => ['İşçilər və icazələr', 'İstifadəçi yarada və icazələri dəyişə bilər'],
        'settings' => ['Ayarlar', 'Platforma ayarlarını dəyişə bilər'],
    ];

    public const TASK_STATUSES = [
        'todo' => ['Gözləyir', 'slate'],
        'in_progress' => ['Davam edir', 'blue'],
        'review' => ['Yoxlamada', 'amber'],
        'done' => ['Bitdi', 'emerald'],
    ];

    public const PRIORITIES = [
        'low' => ['Aşağı', 'slate'],
        'normal' => ['Normal', 'blue'],
        'high' => ['Yüksək', 'amber'],
        'urgent' => ['Təcili', 'rose'],
    ];

    public const TASK_ROLES = [
        'responsible' => 'Məsul şəxs',
        'supporter' => 'Dəstəkçi',
        'watcher' => 'Tag edilib',
    ];

    // Kontent plan versiyası və paylaşım üçün ortaq təsdiq statusları
    public const APPROVAL_STATUSES = [
        'draft' => ['Qaralama', 'slate'],
        'pending' => ['Rəhbər təsdiqi gözləyir', 'amber'],
        'internal_changes' => ['Daxili düzəliş', 'orange'],
        'sent' => ['Müştəri təsdiqi gözləyir', 'blue'],
        'client_changes' => ['Müştəri düzəliş istədi', 'rose'],
        'client_approved' => ['Müştəri təsdiqlədi', 'emerald'],
        'published' => ['Paylaşıldı', 'violet'],
    ];

    // Müştəri tərəfində görünən adlar (daxili statuslar gizlədilir)
    public const CLIENT_STATUSES = [
        'sent' => ['Təsdiqinizi gözləyir', 'blue'],
        'client_changes' => ['Rəyiniz göndərildi, düzəliş edilir', 'rose'],
        'client_approved' => ['Təsdiqləndi', 'emerald'],
        'published' => ['Paylaşıldı', 'violet'],
    ];

    public const POST_TYPES = [
        'post' => ['Post', '#6366f1'],
        'carousel' => ['Karusel', '#0ea5e9'],
        'story' => ['Story', '#f59e0b'],
        'reels' => ['Reels', '#ec4899'],
        'video' => ['Video', '#10b981'],
    ];

    public const PLATFORMS = [
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'linkedin' => 'LinkedIn',
        'youtube' => 'YouTube',
    ];

    public const CLIENT_STATUSES_CRM = [
        'lead' => ['Potensial', 'slate'],
        'negotiation' => ['Danışıqlar', 'amber'],
        'active' => ['Aktiv əməkdaşlıq', 'emerald'],
        'paused' => ['Dayandırılıb', 'orange'],
        'ended' => ['Bitib', 'rose'],
    ];

    public const CLIENT_EVENT_TYPES = [
        'meeting' => 'Görüş',
        'call' => 'Zəng',
        'email' => 'Mail',
        'contract' => 'Müqavilə',
        'note' => 'Qeyd',
    ];

    public const MONTHS = [
        1 => 'Yanvar', 2 => 'Fevral', 3 => 'Mart', 4 => 'Aprel', 5 => 'May', 6 => 'İyun',
        7 => 'İyul', 8 => 'Avqust', 9 => 'Sentyabr', 10 => 'Oktyabr', 11 => 'Noyabr', 12 => 'Dekabr',
    ];

    public const WEEKDAYS_SHORT = ['B.e', 'Ç.a', 'Ç', 'C.a', 'C', 'Ş', 'B'];

    public static function label(array $map, ?string $key): string
    {
        $v = $map[$key] ?? $key ?? '';

        return is_array($v) ? $v[0] : (string) $v;
    }

    public static function color(array $map, ?string $key): string
    {
        $v = $map[$key] ?? null;

        return is_array($v) ? $v[1] : 'slate';
    }

    public static function monthYear(int $year, int $month): string
    {
        return self::MONTHS[$month].' '.$year;
    }

    public static function date($date, bool $withTime = false): string
    {
        if (! $date) {
            return '—';
        }
        $d = $date instanceof \DateTimeInterface ? Carbon::instance($date) : Carbon::parse($date);
        $s = $d->day.' '.self::MONTHS[$d->month].' '.$d->year;

        return $withTime ? $s.', '.$d->format('H:i') : $s;
    }

    public static function shortDate($date): string
    {
        if (! $date) {
            return '—';
        }
        $d = Carbon::parse($date);

        return $d->day.' '.mb_substr(self::MONTHS[$d->month], 0, 3);
    }

    public static function bytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $v = $bytes;
        while ($v >= 1024 && $i < count($units) - 1) {
            $v /= 1024;
            $i++;
        }

        return round($v, $i ? 1 : 0).' '.$units[$i];
    }
}
