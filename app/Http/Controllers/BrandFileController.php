<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Attachment;
use App\Models\Brand;
use App\Models\Folder;
use App\Services\FileStore;
use App\Services\Notifier;
use Illuminate\Http\Request;

class BrandFileController extends Controller
{
    public function index(Request $request, Brand $brand)
    {
        $folder = $request->query('folder') ? $brand->folders()->findOrFail($request->query('folder')) : null;
        $folders = $brand->folders()->withCount('attachments')->get();
        $files = $brand->libraryFiles()->with('uploader')
            ->when($folder, fn ($q) => $q->where('folder_id', $folder->id), fn ($q) => $q->whereNull('folder_id'))
            ->when($request->query('q'), fn ($q, $v) => $q->where('original_name', 'like', "%{$v}%"))
            ->get();

        return view('brands.files', compact('brand', 'folder', 'folders', 'files'));
    }

    public function store(Request $request, Brand $brand)
    {
        $data = $request->validate([
            'attachment_ids' => ['required', 'array'],
            'folder_id' => ['nullable', 'exists:folders,id'],
        ]);
        $folder = isset($data['folder_id']) ? $brand->folders()->findOrFail($data['folder_id']) : null;
        $n = FileStore::claim($data['attachment_ids'], $request->user(), $brand, [
            'brand_id' => $brand->id,
            'folder_id' => $folder?->id,
            'client_visible' => $folder ? $folder->client_visible : true,
        ]);
        if ($n) {
            Activity::log("{$brand->name} fayllarına {$n} fayl yüklədi".($folder ? " ({$folder->name})" : ''), $brand, $brand->id);
            if (! $folder || $folder->client_visible) {
                Notifier::send($brand->clientUsers()->where('is_active', true)->get(), 'Media portfoliosuna yeni fayl əlavə edildi',
                    "{$n} yeni fayl yükləndi".($folder ? " ({$folder->name} qovluğu)" : '').'.', route('portal.media', ['folder' => $folder?->id]), $brand->name);
            }
        }

        return back()->with('success', $n.' fayl yükləndi.');
    }

    public function folder(Request $request, Brand $brand)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80']]);
        $folder = $brand->folders()->create(['name' => $data['name'], 'client_visible' => $request->boolean('client_visible', true)]);

        return redirect()->route('brands.files', [$brand, 'folder' => $folder->id])->with('success', 'Qovluq yaradıldı.');
    }

    public function updateFolder(Request $request, Brand $brand, Folder $folder)
    {
        abort_unless($folder->brand_id === $brand->id, 404);
        $data = $request->validate(['name' => ['required', 'string', 'max:80']]);
        $folder->update(['name' => $data['name'], 'client_visible' => $request->boolean('client_visible')]);
        $folder->attachments()->update(['client_visible' => $folder->client_visible]);

        return back()->with('success', 'Qovluq yeniləndi.');
    }

    public function destroyFolder(Brand $brand, Folder $folder)
    {
        abort_unless($folder->brand_id === $brand->id, 404);
        if ($folder->attachments()->exists()) {
            return back()->withErrors(['folder' => 'Qovluq boş deyil. Əvvəlcə faylları silin.']);
        }
        $folder->delete();

        return redirect()->route('brands.files', $brand)->with('success', 'Qovluq silindi.');
    }

    /** Faylın müştəriyə görünməsini aç/bağla */
    public function toggle(Brand $brand, Attachment $attachment)
    {
        abort_unless($attachment->brand_id === $brand->id, 404);
        $attachment->update(['client_visible' => ! $attachment->client_visible]);

        return back()->with('success', $attachment->client_visible ? 'Fayl müştəriyə görünür.' : 'Fayl müştəridən gizlədildi.');
    }
}
