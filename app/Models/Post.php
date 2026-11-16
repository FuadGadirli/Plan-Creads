<?php

namespace App\Models;

use App\Support\Labels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    public const CLIENT_VISIBLE = ['sent', 'client_changes', 'client_approved', 'published'];

    protected $fillable = [
        'brand_id', 'publish_date', 'publish_time', 'type', 'platforms', 'title', 'caption', 'notes', 'status',
        'created_by', 'reviewed_by', 'sent_at', 'client_decided_at', 'client_decided_by', 'last_reminder_at', 'reminder_count',
    ];

    protected $casts = [
        'publish_date' => 'date',
        'platforms' => 'array',
        'sent_at' => 'datetime',
        'client_decided_at' => 'datetime',
        'last_reminder_at' => 'datetime',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable')->oldest();
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->oldest();
    }

    public function scopeClientVisible(Builder $q): Builder
    {
        return $q->whereIn('status', self::CLIENT_VISIBLE);
    }

    public function typeLabel(): string
    {
        return Labels::label(Labels::POST_TYPES, $this->type);
    }

    public function typeColor(): string
    {
        return Labels::POST_TYPES[$this->type][1] ?? '#6366f1';
    }

    public function isClientVisible(): bool
    {
        return in_array($this->status, self::CLIENT_VISIBLE, true);
    }
}
