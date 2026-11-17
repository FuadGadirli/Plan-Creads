<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    protected $fillable = ['brand_id', 'name', 'client_visible'];

    protected $casts = ['client_visible' => 'boolean'];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }
}
