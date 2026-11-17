<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\Request;

class MediaController extends PortalController
{
    public function index(Request $request)
    {
        $brand = $this->brand($request);
        $folders = $brand->folders()->where('client_visible', true)
            ->withCount(['attachments' => fn ($q) => $q->where('client_visible', true)])->get();
        $folder = $request->query('folder') ? $folders->firstWhere('id', (int) $request->query('folder')) : null;
        $files = $brand->libraryFiles()->where('client_visible', true)
            ->when($folder, fn ($q) => $q->where('folder_id', $folder->id), fn ($q) => $q->whereNull('folder_id'))
            ->get();

        return view('portal.media', compact('brand', 'folders', 'folder', 'files'));
    }
}
