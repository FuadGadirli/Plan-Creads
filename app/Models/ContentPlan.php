<?php

namespace App\Models;

use App\Support\Labels;
use Illuminate\Database\Eloquent\Model;

class ContentPlan extends Model
{
    protected $fillable = ['brand_id', 'year', 'month', 'status'];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function versions()
    {
        return $this->hasMany(ContentPlanVersion::class)->orderByDesc('version');
    }

    public function latestVersion()
    {
        return $this->hasOne(ContentPlanVersion::class)->latestOfMany('version');
    }

    /** Müştəriyə göndərilmiş versiyalar (rəhbər təsdiqindən keçmiş) */
    public function clientVersions()
    {
        return $this->versions()->whereIn('status', ['sent', 'client_changes', 'client_approved']);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->oldest();
    }

    public function title(): string
    {
        return Labels::monthYear($this->year, $this->month);
    }

    /** Statusu son versiyaya görə yenilə */
    public function syncStatus(): void
    {
        $latest = $this->versions()->first();
        $this->update(['status' => $latest?->status ?? 'draft']);
    }

    public function isPastMonth(): bool
    {
        return ($this->year * 12 + $this->month) < (now()->year * 12 + now()->month);
    }
}
