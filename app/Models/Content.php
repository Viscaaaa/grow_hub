<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id', 'category_id', 'community_id', 'title', 'body', 'tags', 'status',
        'reviewer_id', 'review_note', 'reviewed_at', 'published_at',
        'views_count', 'likes_count', 'comments_count', 'shares_count', 'saves_count',
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'community_id' => 'integer',
            'tags' => 'array',
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function community()
    {
        return $this->belongsTo(Community::class);
    }

    public function media()
    {
        return $this->hasMany(ContentMedia::class)->orderBy('sort_order');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function saves()
    {
        return $this->hasMany(Save::class);
    }

    public function shares()
    {
        return $this->hasMany(Share::class);
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_PUBLISHED);
    }

    /** Menambahkan flag is_liked / is_saved untuk user tertentu */
    public function scopeWithViewerState(Builder $q, ?User $user): Builder
    {
        if (! $user) {
            return $q;
        }

        return $q->withExists([
            'likes as is_liked' => fn ($l) => $l->where('user_id', $user->id),
            'saves as is_saved' => fn ($s) => $s->where('user_id', $user->id),
        ]);
    }
}
