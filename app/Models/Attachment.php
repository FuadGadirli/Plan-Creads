<?php

namespace App\Models;

use App\Support\Labels;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = [
        'attachable_type', 'attachable_id', 'brand_id', 'folder_id', 'uploaded_by', 'original_name',
        'mime_type', 'size', 'disk', 'path', 'remote_id', 'client_visible',
    ];

    protected $casts = ['client_visible' => 'boolean', 'size' => 'integer'];

    public function attachable()
    {
        return $this->morphTo();
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function kind(): string
    {
        $m = (string) $this->mime_type;
        $ext = strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION));

        return match (true) {
            str_starts_with($m, 'image/') => 'image',
            str_starts_with($m, 'video/') => 'video',
            str_starts_with($m, 'audio/') => 'audio',
            $m === 'application/pdf' || $ext === 'pdf' => 'pdf',
            in_array($ext, ['ppt', 'pptx', 'key']) => 'slides',
            in_array($ext, ['doc', 'docx', 'txt', 'rtf']) => 'doc',
            in_array($ext, ['xls', 'xlsx', 'csv']) => 'sheet',
            in_array($ext, ['zip', 'rar', '7z']) => 'archive',
            in_array($ext, ['ai', 'psd', 'eps', 'svg', 'indd', 'fig']) => 'design',
            default => 'file',
        };
    }

    public function sizeLabel(): string
    {
        return Labels::bytes((int) $this->size);
    }

    public function url(): string
    {
        return route('files.show', $this);
    }

    public function previewUrl(): string
    {
        return route('files.preview', $this);
    }

    public function thumbUrl(): ?string
    {
        return in_array($this->kind(), ['image', 'video', 'pdf', 'slides']) && ($this->kind() === 'image' || $this->disk === 'onedrive')
            ? route('files.thumb', $this) : null;
    }
}
