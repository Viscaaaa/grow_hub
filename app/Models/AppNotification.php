<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $fillable = ['user_id', 'actor_id', 'type', 'title', 'message', 'content_id', 'link', 'read_at'];

    protected function casts(): array { return ['read_at' => 'datetime']; }

    public function user() { return $this->belongsTo(User::class); }

    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }

    public function content() { return $this->belongsTo(Content::class); }
}
