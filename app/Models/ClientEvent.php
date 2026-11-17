<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientEvent extends Model
{
    protected $fillable = ['client_id', 'user_id', 'type', 'title', 'description', 'happened_at'];

    protected $casts = ['happened_at' => 'datetime'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
