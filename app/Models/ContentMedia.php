<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ContentMedia extends Model
{
    protected $table = 'content_media';

    protected $fillable = ['content_id', 'type', 'path', 'original_name', 'mime', 'size', 'sort_order'];

    protected $appends = ['url'];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->path, 'http')) {
            return $this->path;
        }

        return Storage::disk('public')->url($this->path);
    }
}
