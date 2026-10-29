<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $fillable = ['user_id', 'subject', 'category', 'message', 'status', 'response'];

    public function user() { return $this->belongsTo(User::class); }
}
