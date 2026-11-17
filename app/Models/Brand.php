<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Brand extends Model
{
    protected $fillable = ['name', 'slug', 'color', 'description', 'logo_path', 'client_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (Brand $brand) {
            if (! $brand->slug) {
                $base = Str::slug($brand->name) ?: 'marka';
                $slug = $base;
                $i = 2;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $brand->slug = $slug;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/u', trim($this->name));

        return mb_strtoupper(count($parts) > 1
            ? mb_substr($parts[0], 0, 1).mb_substr($parts[1], 0, 1)
            : mb_substr($this->name, 0, 2));
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function clientUsers()
    {
        return $this->belongsToMany(User::class)->where('role', 'client');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function folders()
    {
        return $this->hasMany(Folder::class)->orderBy('name');
    }

    /** Markanın fayl kitabxanası (Fayllar bölməsi / Media portfolio) */
    public function libraryFiles()
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest();
    }

    public function contentPlans()
    {
        return $this->hasMany(ContentPlan::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
