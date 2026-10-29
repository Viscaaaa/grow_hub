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
        Schema::create('point_rules', function (Blueprint $table) {
            $table->id();
            $table->string('action', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('category', 30)->default('general'); // general | content | social | learning | review
            $table->integer('points')->default(0);
            $table->integer('daily_cap')->nullable(); // null = unlimited
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
        });

        // Seed default rules from system configuration
        $defaultRules = [
            [
                'action' => 'daily_login',
                'name' => 'Login Harian (Streak)',
                'description' => 'Poin apresiasi untuk aktivitas login pertama karyawan setiap harinya guna memicu daily streak.',
                'category' => 'general',
                'points' => 2,
                'daily_cap' => 1,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'action' => 'content_submitted',
                'name' => 'Mengunggah Konten (Draf/Review)',
                'description' => 'Poin yang diperoleh karyawan saat mengajukan draf artikel atau ide untuk ditinjau reviewer.',
                'category' => 'content',
                'points' => 5,
                'daily_cap' => null,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'action' => 'content_published',
                'name' => 'Konten Disetujui Reviewer',
                'description' => 'Bonus poin besar bagi penulis saat konten yang diajukan disetujui dan dipublikasikan di feed.',
                'category' => 'content',
                'points' => 25,
                'daily_cap' => null,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'action' => 'content_viewed',
                'name' => 'Membaca Konten',
                'description' => 'Poin penghargaan bagi karyawan yang membaca dan mempelajari artikel knowledge sharing karyawan lain.',
                'category' => 'content',
                'points' => 1,
                'daily_cap' => 10,
                'is_active' => false, // Default off, admin dapat mengaktifkan kapan saja
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'action' => 'like_received',
                'name' => 'Konten Menerima Like',
                'description' => 'Poin yang didapatkan oleh penulis saat kontennya diapresiasi (disukai) oleh rekan kerja.',
                'category' => 'social',
                'points' => 2,
                'daily_cap' => null,
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'action' => 'comment_given',
                'name' => 'Memberi Komentar Berfaedah',
                'description' => 'Poin partisipasi aktif dalam berdiskusi dan memberikan feedback di kolom komentar.',
                'category' => 'social',
                'points' => 1,
                'daily_cap' => 10,
                'is_active' => true,
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'action' => 'comment_received',
                'name' => 'Konten Menerima Komentar',
                'description' => 'Poin bagi pembuat postingan saat kontennya berhasil memancing interaksi dan diskusi rekan kerja.',
                'category' => 'social',
                'points' => 3,
                'daily_cap' => null,
                'is_active' => true,
                'sort_order' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'action' => 'share_given',
                'name' => 'Membagikan Konten (Share)',
                'description' => 'Poin bagi pengguna yang membagikan tautan postingan bermanfaat ke rekan kerja.',
                'category' => 'social',
                'points' => 1,
                'daily_cap' => 10,
                'is_active' => true,
                'sort_order' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'action' => 'share_received',
                'name' => 'Konten Dibagikan Orang Lain',
                'description' => 'Poin bagi penulis jika kontennya dianggap sangat bermanfaat hingga dibagikan oleh rekan kerja.',
                'category' => 'social',
                'points' => 2,
                'daily_cap' => null,
                'is_active' => true,
                'sort_order' => 9,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'action' => 'save_received',
                'name' => 'Konten Disimpan / Bookmark',
                'description' => 'Poin bagi penulis ketika artikelnya disimpan ke daftar bacaan pribadi rekan kerja.',
                'category' => 'social',
                'points' => 2,
                'daily_cap' => null,
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'action' => 'review_done',
                'name' => 'Menyelesaikan Review Konten',
                'description' => 'Poin apresiasi bagi reviewer/admin yang aktif mengevaluasi antrean konten.',
                'category' => 'review',
                'points' => 3,
                'daily_cap' => null,
                'is_active' => true,
                'sort_order' => 11,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'action' => 'course_completed',
                'name' => 'Menyelesaikan Modul Kursus',
                'description' => 'Poin kelulusan materi mandiri retail di Learning Center.',
                'category' => 'learning',
                'points' => 15,
                'daily_cap' => null,
                'is_active' => true,
                'sort_order' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('point_rules')->insert($defaultRules);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_rules');
    }
};
