<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Brand;
use App\Models\ContentPlanVersion;
use App\Models\Post;
use App\Models\User;
use App\Services\FileStore;
use App\Services\OneDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public static function canView(User $user, Attachment $a): bool
    {
        if ($user->isStaff()) {
            return true;
        }
        if (! $a->brand_id || ! $a->client_visible || ! $user->brands()->whereKey($a->brand_id)->exists()) {
            return false;
        }
        $target = $a->attachable;

        return match (true) {
            $target instanceof Brand => ! $a->folder || $a->folder->client_visible,
            $target instanceof Post => $target->isClientVisible(),
            $target instanceof ContentPlanVersion => $target->isClientVisible(),
            default => false,
        };
    }

    private function guard(Request $request, Attachment $a): void
    {
        abort_unless(self::canView($request->user(), $a), 403, 'Bu fayla giriş icazəniz yoxdur.');
    }

    /** Faylın özü: brauzerdə açılır və ya ?download=1 ilə yüklənir */
    public function show(Request $request, Attachment $attachment)
    {
        $this->guard($request, $attachment);

        if ($attachment->disk === 'onedrive') {
            try {
                return redirect()->away(OneDrive::downloadUrl($attachment->remote_id));
            } catch (\Throwable $e) {
                report($e);
                abort(502, 'OneDrive-dan fayl alınmadı. Bir az sonra yenidən yoxlayın.');
            }
        }

        abort_unless($attachment->path && Storage::disk('local')->exists($attachment->path), 404);
        $headers = ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream'];

        return $request->boolean('download')
            ? Storage::disk('local')->download($attachment->path, $attachment->original_name, $headers)
            : response()->file(Storage::disk('local')->path($attachment->path), $headers + [
                'Content-Disposition' => 'inline; filename="'.addslashes($attachment->original_name).'"',
            ]);
    }

    /** Yükləmədən baxış səhifəsi */
    public function preview(Request $request, Attachment $attachment)
    {
        $this->guard($request, $attachment);
        $embed = null;
        if ($attachment->disk === 'onedrive' && in_array($attachment->kind(), ['pdf', 'slides', 'doc', 'sheet'])) {
            try {
                $embed = OneDrive::previewUrl($attachment->remote_id);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return view('files.preview', ['file' => $attachment, 'embed' => $embed, 'back' => url()->previous()]);
    }

    public function thumb(Request $request, Attachment $attachment)
    {
        $this->guard($request, $attachment);
        if ($attachment->disk === 'onedrive') {
            try {
                $url = OneDrive::thumbnailUrl($attachment->remote_id);
            } catch (\Throwable) {
                $url = null;
            }

            return $url ? redirect()->away($url) : abort(404);
        }
        abort_unless($attachment->kind() === 'image', 404);

        return $this->show($request, $attachment);
    }

    public function destroy(Request $request, Attachment $attachment)
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $attachment->uploaded_by === $user->id, 403, 'Faylı yalnız yükləyən şəxs və ya rəhbərlik silə bilər.');
        abort_if($attachment->attachable instanceof ContentPlanVersion, 422, 'Kontent plan versiyasının faylı silinə bilməz.');
        FileStore::delete($attachment);

        return back()->with('success', 'Fayl silindi.');
    }
}
