<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentPlanVersion extends Model
{
    protected $fillable = [
        'content_plan_id', 'version', 'attachment_id', 'note', 'status', 'uploaded_by',
        'reviewed_by', 'reviewed_at', 'client_decided_by', 'client_decided_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'client_decided_at' => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(ContentPlan::class, 'content_plan_id');
    }

    public function attachment()
    {
        return $this->belongsTo(Attachment::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function clientDecider()
    {
        return $this->belongsTo(User::class, 'client_decided_by');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->oldest();
    }

    public function isClientVisible(): bool
    {
        return in_array($this->status, ['sent', 'client_changes', 'client_approved'], true);
    }
}
