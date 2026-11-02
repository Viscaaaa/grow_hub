<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LevelRule extends Model
{
    use HasFactory;

    protected $table = 'level_rules';

    protected $fillable = [
        'level',
        'name',
        'min_points',
        'description',
        'badge_icon',
        'badge_color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'min_points' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Ambil seluruh tingkatan level aktif yang diurutkan berdasarkan poin minimal.
     */
    public static function getActiveTiers()
    {
        return static::active()
            ->orderBy('min_points', 'asc')
            ->orderBy('level', 'asc')
            ->get();
    }
}
