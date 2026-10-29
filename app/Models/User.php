<?php

namespace App\Models;

use App\Services\LevelService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nip', 'name', 'email', 'password', 'role', 'department', 'position',
        'location', 'avatar', 'bio', 'points', 'current_streak', 'longest_streak',
        'last_activity_date', 'is_active', 'must_change_password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $appends = ['avatar_url', 'level_info'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_activity_date' => 'date',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'points' => 'integer',
            'current_streak' => 'integer',
            'longest_streak' => 'integer',
        ];
    }

    public function contents()
    {
        return $this->hasMany(Content::class);
    }

    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function saves()
    {
        return $this->hasMany(Save::class);
    }

    public function communities()
    {
        return $this->belongsToMany(Community::class, 'community_members')->withTimestamps();
    }

    public function completedCourses()
    {
        return $this->belongsToMany(Course::class, 'course_completions')->withTimestamps();
    }

    public function appNotifications()
    {
        return $this->hasMany(AppNotification::class);
    }

    public function isReviewer(): bool
    {
        return in_array($this->role, ['reviewer', 'admin'], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? Storage::disk('public')->url($this->avatar) : null;
    }

    public function getLevelInfoAttribute(): array
    {
        return LevelService::forPoints($this->points ?? 0);
    }
}
