<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'action',
        'name',
        'description',
        'category',
        'points',
        'daily_cap',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'daily_cap' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Scope to only active rules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
