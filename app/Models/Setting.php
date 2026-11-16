<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'agency_name' => 'Creads',
        'reminder_enabled' => '1',
        'reminder_interval_hours' => '1',
        'reminder_start_hour' => '9',
        'reminder_end_hour' => '19',
        'reminder_max_per_day' => '4',
        'reminder_days_before' => '2',
    ];

    public static function get(string $key, $default = null)
    {
        $all = Cache::rememberForever('settings.all', fn () => static::query()->pluck('value', 'key')->all());

        return $all[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function put(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings.all');
    }

    public static function getSecret(string $key): ?string
    {
        $v = static::get($key);
        if (! $v) {
            return null;
        }
        try {
            return Crypt::decryptString($v);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function putSecret(string $key, ?string $value): void
    {
        static::put($key, $value === null ? null : Crypt::encryptString($value));
    }
}
