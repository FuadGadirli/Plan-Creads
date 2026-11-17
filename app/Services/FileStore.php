<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Fayl yükləmə mərkəzi.
 * Brauzer faylı 5 MB-lıq hissələrlə göndərir. OneDrive qoşulubsa hissələr birbaşa
 * OneDrive-a ötürülür (serverdə saxlanmır). Qoşulmayıbsa müvəqqəti olaraq serverdə saxlanır.
 */
class FileStore
{
    // OneDrive tələbi: hissə ölçüsü 320 KiB-ın misli olmalıdır. 16 × 320 KiB = 5 MiB
    public const CHUNK_SIZE = 327680 * 16;

    public const SCOPES = [
        'files' => 'Fayllar',
        'tasks' => 'Tapşırıqlar',
        'content' => 'Kontent plan',
        'posts' => 'Paylaşımlar',
        'crm' => 'Əməkdaşlıq',
    ];

    public static function activeDisk(): string
    {
        return OneDrive::connected() ? 'onedrive' : 'local';
    }

    public static function folderPath(?Brand $brand, string $scope, ?string $sub = null): string
    {
        $root = OneDrive::cleanSegment(config('services.microsoft.root_folder', 'Creads Plan'));
        $parts = [$root];
        $parts[] = $brand ? OneDrive::cleanSegment($brand->name) : 'Ümumi';
        $parts[] = self::SCOPES[$scope] ?? 'Digər';
        if ($sub) {
            $parts[] = OneDrive::cleanSegment($sub);
        }

        return implode('/', $parts);
    }

    public static function start(User $user, string $name, int $size, ?string $mime, ?Brand $brand, string $scope, ?string $sub = null): array
    {
        $id = (string) Str::uuid();
        $name = OneDrive::cleanSegment($name);
        $disk = self::activeDisk();
        $state = [
            'user_id' => $user->id,
            'name' => $name,
            'size' => $size,
            'mime' => $mime ?: 'application/octet-stream',
            'brand_id' => $brand?->id,
            'disk' => $disk,
            'received' => 0,
        ];

        if ($disk === 'onedrive') {
            $state['upload_url'] = OneDrive::createUploadSession(self::folderPath($brand, $scope, $sub).'/'.$name);
        } else {
            $state['path'] = 'files/'.($brand?->slug ?? 'umumi').'/'.$scope.'/'.now()->format('Y-m').'/'.Str::random(8).'-'.$name;
        }

        Cache::put("upload.{$id}", $state, now()->addDay());

        return ['id' => $id, 'chunk_size' => self::CHUNK_SIZE, 'disk' => $disk];
    }

    /** Hissəni qəbul edir. Son hissədə Attachment qaytarır. */
    public static function chunk(User $user, string $id, string $bytes, int $offset): ?Attachment
    {
        $state = Cache::get("upload.{$id}");
        if (! $state || $state['user_id'] !== $user->id) {
            throw new RuntimeException('Yükləmə sessiyası tapılmadı və ya vaxtı bitib.');
        }
        if ($offset !== $state['received']) {
            throw new RuntimeException('Hissələrin ardıcıllığı pozulub.');
        }

        $len = strlen($bytes);
        $isLast = $offset + $len >= $state['size'];
        $remote = null;

        if ($state['disk'] === 'onedrive') {
            $remote = OneDrive::uploadChunk($state['upload_url'], $bytes, $offset, $state['size']);
        } else {
            $full = Storage::disk('local')->path($state['path']);
            if (! is_dir(dirname($full))) {
                mkdir(dirname($full), 0775, true);
            }
            file_put_contents($full, $bytes, $offset === 0 ? 0 : FILE_APPEND);
        }

        $state['received'] = $offset + $len;
        Cache::put("upload.{$id}", $state, now()->addDay());

        if (! $isLast) {
            return null;
        }

        Cache::forget("upload.{$id}");

        return Attachment::create([
            'brand_id' => $state['brand_id'],
            'uploaded_by' => $user->id,
            'original_name' => $remote['name'] ?? $state['name'],
            'mime_type' => $remote['file']['mimeType'] ?? $state['mime'],
            'size' => $remote['size'] ?? $state['size'],
            'disk' => $state['disk'],
            'path' => $state['path'] ?? null,
            'remote_id' => $remote['id'] ?? null,
        ]);
    }

    /** Formada göndərilən, hələ heç yerə bağlanmamış faylları obyektə bağlayır */
    public static function claim(array $ids, User $user, $attachable, array $extra = []): int
    {
        $ids = array_filter(array_map('intval', $ids));
        if (! $ids) {
            return 0;
        }

        return Attachment::whereIn('id', $ids)
            ->where('uploaded_by', $user->id)
            ->whereNull('attachable_id')
            ->update(array_merge([
                'attachable_type' => $attachable->getMorphClass(),
                'attachable_id' => $attachable->getKey(),
            ], $extra));
    }

    public static function delete(Attachment $a): void
    {
        try {
            if ($a->disk === 'onedrive' && $a->remote_id) {
                OneDrive::delete($a->remote_id);
            } elseif ($a->path) {
                Storage::disk('local')->delete($a->path);
            }
        } catch (\Throwable $e) {
            report($e);
        }
        $a->delete();
    }
}
