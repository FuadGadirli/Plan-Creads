<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name', 'industry', 'website', 'contact_person', 'contact_position', 'email', 'phone',
        'address', 'status', 'first_contact_at', 'started_at', 'notes', 'owner_id',
    ];

    protected $casts = [
        'first_contact_at' => 'date',
        'started_at' => 'date',
    ];

    public function events()
    {
        return $this->hasMany(ClientEvent::class)->orderByDesc('happened_at');
    }

    public function brands()
    {
        return $this->hasMany(Brand::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest();
    }
}
