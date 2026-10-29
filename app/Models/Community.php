<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Community extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'icon', 'color', 'created_by'];

    public function members()
    {
        return $this->belongsToMany(User::class, 'community_members')->withPivot('role')->withTimestamps();
    }

    public function contents()
    {
        return $this->hasMany(Content::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
