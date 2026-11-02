<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('level_rules', function (Blueprint $table) {
            $table->id();
            $table->integer('level')->index();
            $table->string('name', 100);
            $table->integer('min_points')->default(0)->index();
            $table->text('description')->nullable();
            $table->string('badge_icon', 50)->default('award');
            $table->string('badge_color', 50)->default('#10b981');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        // Seed default 8 levels from gamification config
        $defaultLevels = [
            [
                'level' => 1,
                'name' => 'Seedling',
                'min_points' => 0,
                'description' => 'Tingkat pemula untuk karyawan yang baru memulai perjalanan pengetahuan di GrowHub.',
                'badge_icon' => 'sprout',
                'badge_color' => '#10b981',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'level' => 2,
                'name' => 'Sprout',
                'min_points' => 100,
                'description' => 'Mulai aktif membaca dan berbagi ide pertama di platform.',
                'badge_icon' => 'leaf',
                'badge_color' => '#059669',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'level' => 3,
                'name' => 'Sapling',
                'min_points' => 250,
                'description' => 'Membangun kebiasaan berbagi pengetahuan dan berdiskusi dengan rekan kerja.',
                'badge_icon' => 'tree',
                'badge_color' => '#0d9488',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'level' => 4,
                'name' => 'Grower',
                'min_points' => 500,
                'description' => 'Kontributor reguler yang konsisten membagikan wawasan retail berkualitas.',
                'badge_icon' => 'flame',
                'badge_color' => '#3b82f6',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'level' => 5,
                'name' => 'Cultivator',
                'min_points' => 900,
                'description' => 'Penggerak komunitas pengetahuan yang aktif memberi solusi.',
                'badge_icon' => 'award',
                'badge_color' => '#6366f1',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'level' => 6,
                'name' => 'Harvester',
                'min_points' => 1400,
                'description' => 'Ahli operasional retail yang karyanya banyak diapresiasi dan disimpan.',
                'badge_icon' => 'star',
                'badge_color' => '#8b5cf6',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'level' => 7,
                'name' => 'Mentor',
                'min_points' => 2000,
                'description' => 'Teladan inspiratif dan mentor bagi karyawan lain di seluruh cabang.',
                'badge_icon' => 'sparkles',
                'badge_color' => '#ec4899',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'level' => 8,
                'name' => 'Evergreen',
                'min_points' => 3000,
                'description' => 'Tingkat penguasaan tertinggi, pilar utama ekosistem pengetahuan GrowHub.',
                'badge_icon' => 'crown',
                'badge_color' => '#f59e0b',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('level_rules')->insert($defaultLevels);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('level_rules');
    }
};
