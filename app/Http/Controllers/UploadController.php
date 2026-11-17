<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Services\FileStore;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UploadController extends Controller
{
    public function start(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'size' => ['required', 'integer', 'min:1', 'max:'.(250 * 1024 * 1024 * 1024)],
            'mime' => ['nullable', 'string', 'max:150'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'scope' => ['required', Rule::in(array_keys(FileStore::SCOPES))],
            'sub' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            return response()->json(FileStore::start(
                $request->user(), $data['name'], (int) $data['size'], $data['mime'] ?? null,
                isset($data['brand_id']) ? Brand::find($data['brand_id']) : null, $data['scope'], $data['sub'] ?? null,
            ));
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Yükləmə başlamadı: '.$e->getMessage()], 500);
        }
    }

    public function chunk(Request $request, string $id)
    {
        $offset = (int) $request->header('X-Offset', 0);
        $bytes = $request->getContent();
        if ($bytes === '' || strlen($bytes) > FileStore::CHUNK_SIZE) {
            return response()->json(['message' => 'Yanlış hissə ölçüsü.'], 422);
        }

        try {
            $a = FileStore::chunk($request->user(), $id, $bytes, $offset);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json($a ? [
            'done' => true,
            'attachment' => ['id' => $a->id, 'name' => $a->original_name, 'size' => $a->sizeLabel(), 'kind' => $a->kind()],
        ] : ['done' => false]);
    }
}
