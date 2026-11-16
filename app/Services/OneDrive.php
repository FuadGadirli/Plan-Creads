<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Microsoft Graph vasitəsilə OneDrive ilə işləyən sinif.
 * İcazə tipi: delegated (Fuad öz hesabı ilə bir dəfə "OneDrive-ı qoş" edir,
 * platforma refresh token saxlayır və sonra arxa planda işləyir).
 */
class OneDrive
{
    private const GRAPH = 'https://graph.microsoft.com/v1.0';

    public const SCOPES = 'offline_access User.Read Files.ReadWrite';

    public static function configured(): bool
    {
        return (bool) (config('services.microsoft.client_id') && config('services.microsoft.client_secret'));
    }

    public static function connected(): bool
    {
        return self::configured() && (bool) Setting::getSecret('onedrive_refresh_token');
    }

    public static function accountLabel(): ?string
    {
        return Setting::get('onedrive_account');
    }

    private static function authBase(): string
    {
        $tenant = config('services.microsoft.tenant') ?: 'common';

        return "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0";
    }

    public static function redirectUri(): string
    {
        return config('services.microsoft.redirect') ?: route('onedrive.callback');
    }

    public static function authorizeUrl(string $state): string
    {
        return self::authBase().'/authorize?'.http_build_query([
            'client_id' => config('services.microsoft.client_id'),
            'response_type' => 'code',
            'redirect_uri' => self::redirectUri(),
            'response_mode' => 'query',
            'scope' => self::SCOPES,
            'state' => $state,
            'prompt' => 'select_account',
        ]);
    }

    public static function handleCallback(string $code): void
    {
        $res = Http::asForm()->post(self::authBase().'/token', [
            'client_id' => config('services.microsoft.client_id'),
            'client_secret' => config('services.microsoft.client_secret'),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => self::redirectUri(),
            'scope' => self::SCOPES,
        ]);
        if (! $res->successful()) {
            throw new RuntimeException('Microsoft giriş xətası: '.($res->json('error_description') ?? $res->body()));
        }
        self::storeTokens($res->json());

        $me = Http::withToken($res->json('access_token'))->get(self::GRAPH.'/me')->json();
        Setting::put('onedrive_account', $me['userPrincipalName'] ?? $me['mail'] ?? null);
    }

    public static function disconnect(): void
    {
        Setting::putSecret('onedrive_refresh_token', null);
        Setting::put('onedrive_account', null);
        Cache::forget('onedrive.access_token');
    }

    private static function storeTokens(array $data): void
    {
        if (! empty($data['refresh_token'])) {
            Setting::putSecret('onedrive_refresh_token', $data['refresh_token']);
        }
        Cache::put('onedrive.access_token', $data['access_token'], now()->addSeconds(max(60, ($data['expires_in'] ?? 3600) - 300)));
    }

    public static function token(): string
    {
        if ($t = Cache::get('onedrive.access_token')) {
            return $t;
        }
        $refresh = Setting::getSecret('onedrive_refresh_token');
        if (! $refresh) {
            throw new RuntimeException('OneDrive qoşulmayıb.');
        }
        $res = Http::asForm()->post(self::authBase().'/token', [
            'client_id' => config('services.microsoft.client_id'),
            'client_secret' => config('services.microsoft.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh,
            'scope' => self::SCOPES,
        ]);
        if (! $res->successful()) {
            throw new RuntimeException('OneDrive token yenilənmədi: '.($res->json('error_description') ?? $res->body()));
        }
        self::storeTokens($res->json());

        return $res->json('access_token');
    }

    private static function api()
    {
        return Http::withToken(self::token())->baseUrl(self::GRAPH)->timeout(60);
    }

    /** "Creads Plan/Marka/Fayllar/fayl.jpg" kimi yolu Graph üçün kodlaşdırır */
    public static function encodePath(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', trim($path, '/'))));
    }

    public static function cleanSegment(string $s): string
    {
        $s = preg_replace('/[\\\\\/:*?"<>|#%]+/u', '-', $s);
        $s = trim(preg_replace('/\s+/u', ' ', $s), ' .');

        return $s === '' ? 'fayl' : Str::limit($s, 120, '');
    }

    /** Böyük fayllar üçün yükləmə sessiyası yaradır */
    public static function createUploadSession(string $path): string
    {
        $res = self::api()->post('/me/drive/root:/'.self::encodePath($path).':/createUploadSession', [
            'item' => ['@microsoft.graph.conflictBehavior' => 'rename'],
        ]);
        if (! $res->successful()) {
            throw new RuntimeException('OneDrive yükləmə sessiyası açılmadı: '.$res->body());
        }

        return $res->json('uploadUrl');
    }

    /**
     * Bir hissəni (chunk) yükləmə sessiyasına göndərir.
     * Son hissədə OneDrive faylın məlumatını (id, name, size) qaytarır.
     */
    public static function uploadChunk(string $uploadUrl, string $bytes, int $offset, int $total): ?array
    {
        $len = strlen($bytes);
        $end = $offset + $len - 1;
        // uploadUrl öz-özlüyündə icazəlidir, Authorization başlığı göndərilmir
        $res = Http::withHeaders([
            'Content-Length' => (string) $len,
            'Content-Range' => "bytes {$offset}-{$end}/{$total}",
        ])->timeout(120)->withBody($bytes, 'application/octet-stream')->put($uploadUrl);

        if (! in_array($res->status(), [200, 201, 202], true)) {
            throw new RuntimeException('OneDrive hissə yükləmə xətası ('.$res->status().'): '.$res->body());
        }

        return in_array($res->status(), [200, 201], true) ? $res->json() : null;
    }

    public static function item(string $id): array
    {
        return self::api()->get("/me/drive/items/{$id}")->throw()->json();
    }

    public static function downloadUrl(string $id): string
    {
        return Cache::remember("onedrive.dl.{$id}", now()->addMinutes(30), function () use ($id) {
            $item = self::item($id);

            return $item['@microsoft.graph.downloadUrl'] ?? throw new RuntimeException('Yükləmə linki tapılmadı');
        });
    }

    public static function thumbnailUrl(string $id, string $size = 'large'): ?string
    {
        return Cache::remember("onedrive.th.{$id}.{$size}", now()->addMinutes(30), function () use ($id, $size) {
            $res = self::api()->get("/me/drive/items/{$id}/thumbnails/0/{$size}");

            return $res->successful() ? $res->json('url') : null;
        });
    }

    /** PDF, PPTX, Word və video üçün brauzerdə açılan ön baxış linki */
    public static function previewUrl(string $id): ?string
    {
        return Cache::remember("onedrive.pv.{$id}", now()->addMinutes(10), function () use ($id) {
            $res = self::api()->post("/me/drive/items/{$id}/preview", (object) []);

            return $res->successful() ? $res->json('getUrl') : null;
        });
    }

    public static function delete(string $id): void
    {
        self::api()->delete("/me/drive/items/{$id}");
    }

    public static function quota(): ?array
    {
        try {
            return Cache::remember('onedrive.quota', now()->addMinutes(15), fn () => self::api()->get('/me/drive')->throw()->json('quota'));
        } catch (\Throwable) {
            return null;
        }
    }
}
