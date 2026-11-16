<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'title', 'description', 'brand_id', 'status', 'priority', 'due_date', 'position', 'created_by', 'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('role');
    }

    public function responsibles()
    {
        return $this->users()->wherePivot('role', 'responsible');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->oldest();
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest();
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->status !== 'done' && $this->due_date->endOfDay()->isPast();
    }

    /** İstifadəçinin görə bildiyi tapşırıqlar */
    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        if ($user->hasPerm('tasks.all')) {
            return $q;
        }

        return $q->where(function ($q) use ($user) {
            $q->where('created_by', $user->id)
                ->orWhereHas('users', fn ($u) => $u->where('users.id', $user->id));
        });
    }

    public function scopeInvolving(Builder $q, User $user): Builder
    {
        return $q->whereHas('users', fn ($u) => $u->where('users.id', $user->id));
    }

    public function canView(User $user): bool
    {
        return $user->hasPerm('tasks.all') || $this->created_by === $user->id
            || $this->users->contains('id', $user->id);
    }
}
