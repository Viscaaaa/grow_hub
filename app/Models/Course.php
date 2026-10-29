<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = ['title', 'slug', 'category', 'level', 'summary', 'body', 'video_url', 'cover_color', 'duration_minutes', 'points_reward', 'is_published'];

    protected function casts(): array { return ['is_published' => 'boolean']; }

    public function completions() { return $this->belongsToMany(User::class, 'course_completions')->withTimestamps(); }
}
