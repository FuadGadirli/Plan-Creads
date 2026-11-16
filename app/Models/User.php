<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Support\Labels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'position', 'phone', 'avatar_path',
        'permissions', 'client_id', 'is_active', 'email_notifications', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
            'is_active' => 'boolean',
            'email_notifications' => 'boolean',
        ];
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['admin', 'employee'], true);
    }

    public function hasPerm(string $perm): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        if ($this->isClient()) {
            return false;
        }

        return in_array($perm, $this->permissions ?? [], true);
    }

    public function roleLabel(): string
    {
        return Labels::ROLES[$this->role] ?? $this->role;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/u', trim($this->name));
        $i = mb_substr($parts[0] ?? '', 0, 1);
        if (count($parts) > 1) {
            $i .= mb_substr(end($parts), 0, 1);
        }

        return mb_strtoupper($i);
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /** Müştəri istifadəçisinin görə bildiyi markalar */
    public function brands()
    {
        return $this->belongsToMany(Brand::class);
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class)->withPivot('role');
    }

    public function scopeStaff(Builder $q): Builder
    {
        return $q->whereIn('role', ['admin', 'employee']);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /** Təsdiq hüququ olan aktiv işçilər */
    public static function approvers()
    {
        return static::staff()->active()->get()->filter(fn (User $u) => $u->hasPerm('approve'));
    }

    /** Bu markanın aktiv müştəri istifadəçiləri */
    public static function clientUsersOf(Brand $brand)
    {
        return $brand->clientUsers()->where('is_active', true)->get();
    }
}
