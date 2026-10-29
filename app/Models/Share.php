<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Share extends Model
{
    protected $fillable = ['user_id', 'content_id', 'channel'];

    public function user() { return $this->belongsTo(User::class); }

    public function content() { return $this->belongsTo(Content::class); }
}
