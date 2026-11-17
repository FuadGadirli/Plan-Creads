<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = ['user_id', 'brand_id', 'subject_type', 'subject_id', 'description'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public static function log(string $description, ?Model $subject = null, ?int $brandId = null): void
    {
        static::create([
            'user_id' => auth()->id(),
            'brand_id' => $brandId ?? ($subject?->brand_id ?? null),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description,
        ]);
    }
}
