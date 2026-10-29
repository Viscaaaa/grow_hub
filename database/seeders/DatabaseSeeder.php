<?php

namespace Database\Seeders;

use App\Models\AppNotification;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Community;
use App\Models\Content;
use App\Models\Course;
use App\Models\Like;
use App\Models\PointTransaction;
use App\Models\Save;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(42);
        $points = config('gamification.points');

        // ------------------------------------------------------------------
        // Kategori konten
        // ------------------------------------------------------------------
        $categories = collect([
            ['Best Practice', 'best-practice', 'Award', '#1e8c45', 'Praktik terbaik yang terbukti berhasil di lapangan'],
            ['Tips & Trick', 'tips-trick', 'Lightbulb', '#f59e0b', 'Cara cepat dan cerdas menyelesaikan pekerjaan'],
            ['Lesson Learned', 'lesson-learned', 'BookOpen', '#2563eb', 'Pelajaran berharga dari pengalaman'],
            ['Innovation & Ideas', 'innovation-ideas', 'Rocket', '#8b5cf6', 'Ide dan masukan untuk kemajuan perusahaan'],
            ['Document / SOP', 'document-sop', 'FileText', '#0f766e', 'Dokumen, panduan dan standar operasional'],
            ['Success Story', 'success-story', 'Trophy', '#e11d48', 'Kisah sukses & perjalanan karier karyawan'],
            ['Group Discussion', 'group-discussion', 'MessagesSquare', '#0891b2', 'Diskusi terbuka antar karyawan'],
            ['Video & Photo', 'video-photo', 'Camera', '#142c14', 'Berbagi momen dan pengetahuan lewat visual'],
        ])->map(fn ($c, $i) => Category::create([
            'name' => $c[0], 'slug' => $c[1], 'icon' => $c[2], 'color' => $c[3], 'description' => $c[4], 'sort_order' => $i,
        ]))->keyBy('slug');

        // ------------------------------------------------------------------
        // Karyawan (username & password = NIP)
        // ------------------------------------------------------------------
        $people = [
            ['1000000001', 'Administrator GrowHub', 'admin', 'Human Capital', 'HC System Administrator'],
            ['1000000002', 'Rina Kartika', 'reviewer', 'Human Capital', 'Learning & Development Lead'],
            ['1000000003', 'Bima Prasetyo', 'reviewer', 'Store Operations', 'Area Operations Manager'],
            ['202301001', 'Andi Saputra', 'employee', 'Store Operations', 'Store Manager'],
            ['202301002', 'Siti Nurhaliza', 'employee', 'Merchandising', 'Category Specialist'],
            ['202301003', 'Dewi Lestari', 'employee', 'Customer Service', 'CS Supervisor'],
            ['202301004', 'Rizky Ramadhan', 'employee', 'Supply Chain', 'Warehouse Supervisor'],
            ['202301005', 'Putri Ayu Wulandari', 'employee', 'Marketing', 'Digital Marketing Officer'],
            ['202301006', 'Fajar Nugroho', 'employee', 'IT', 'System Analyst'],
            ['202301007', 'Maya Anggraini', 'employee', 'Finance', 'Finance Analyst'],
            ['202301008', 'Hendra Gunawan', 'employee', 'Store Operations', 'Assistant Store Manager'],
            ['202301009', 'Lina Marlina', 'employee', 'Store Operations', 'Senior Cashier'],
            ['202301010', 'Yoga Pratama', 'employee', 'Supply Chain', 'Inventory Controller'],
            ['202301011', 'Nadia Safitri', 'employee', 'Human Capital', 'HR Business Partner'],
            ['202301012', 'Agus Setiawan', 'employee', 'Merchandising', 'Visual Merchandiser'],
            ['202301013', 'Indah Permatasari', 'employee', 'Customer Service', 'Customer Experience Officer'],
            ['2023010014', 'Dimas Aditya', 'employee', 'IT', 'Frontend Developer'],
            ['2023010015', 'Ratna Sari', 'employee', 'Marketing', 'Brand Executive'],
            ['2023010016', 'Teguh Wibowo', 'employee', 'Store Operations', 'Store Crew Leader'],
            ['2023010017', 'Ayu Kusuma', 'employee', 'Finance', 'Tax Officer'],
        ];

        $users = collect($people)->map(fn ($p) => User::create([
            'nip' => $p[0],
            'name' => $p[1],
            'role' => $p[2],
            'department' => $p[3],
            'position' => $p[4],
            'email' => Str::slug($p[1], '.').'@growhub.co.id',
            'password' => Hash::make($p[0]),
            'location' => collect(['Jakarta', 'Bandung', 'Surabaya', 'Semarang', 'Medan', 'Makassar'])->random(),
            'bio' => 'Bertumbuh bersama, berbagi untuk kemajuan.',
        ]));
        $employees = $users->where('role', 'employee')->values();
        $reviewers = $users->whereIn('role', ['reviewer', 'admin'])->values();

        // ------------------------------------------------------------------
        // Komunitas
        // ------------------------------------------------------------------
        $communities = collect([
            ['Store Excellence', 'Berbagi praktik terbaik operasional toko, dari display hingga pelayanan.', 'Building', '#1e8c45'],
            ['Customer Delight', 'Komunitas untuk meningkatkan pengalaman pelanggan di setiap titik sentuh.', 'Heart', '#e11d48'],
            ['Supply Chain Hub', 'Diskusi seputar inventory, warehouse, dan distribusi.', 'Compass', '#0891b2'],
            ['Digital & Innovation', 'Ide digitalisasi, otomasi, dan inovasi proses kerja.', 'Rocket', '#8b5cf6'],
            ['Leadership Circle', 'Ruang belajar para pemimpin tim dan calon pemimpin.', 'Crown', '#f59e0b'],
            ['Wellness & Life', 'Tips kesehatan, work-life balance, dan kegiatan karyawan.', 'Leaf', '#142c14'],
        ])->map(function ($c) use ($users) {
            $community = Community::create([
                'name' => $c[0], 'slug' => Str::slug($c[0]), 'description' => $c[1],
                'icon' => $c[2], 'color' => $c[3], 'created_by' => $users[0]->id,
            ]);
            $community->members()->attach($users->random(mt_rand(6, 14))->pluck('id'));

            return $community;
        })->values();

        // ------------------------------------------------------------------
        // Media contoh (opsional – jika file ada di storage/app/public/seed)
        // ------------------------------------------------------------------
        $seedImages = collect(Storage::disk('public')->files('seed'))
            ->filter(fn ($f) => preg_match('/\.(png|jpe?g|webp)$/i', $f))->values();

        // ------------------------------------------------------------------
        // Konten
        // ------------------------------------------------------------------
        $samples = [
            ['best-practice', 'Cara Kami Menurunkan Antrian Kasir 40% Saat Weekend', "Selama 3 bulan terakhir, tim toko kami mencoba beberapa pendekatan untuk mengurangi antrian kasir di jam sibuk.\n\n1. Membuka 'express lane' khusus belanja di bawah 10 item.\n2. Menempatkan crew 'queue buster' dengan handheld POS.\n3. Menjadwalkan istirahat kasir di luar jam puncak (11.00–13.00 dan 17.00–19.00).\n\nHasilnya waktu tunggu rata-rata turun dari 8 menit menjadi 4,8 menit dan NPS naik 12 poin. Semoga bisa diterapkan di toko lain!", ['kasir', 'operasional', 'customer-experience'], 0],
            ['tips-trick', '5 Shortcut Excel yang Menghemat Waktu Rekap Stok Harian', "Buat teman-teman yang setiap hari merekap stok:\n\n• Ctrl + T untuk mengubah data menjadi tabel (otomatis filter & format)\n• Alt + = untuk AutoSum\n• Ctrl + Shift + L untuk filter cepat\n• XLOOKUP untuk mencocokkan SKU antar sheet\n• Pivot Table + Slicer untuk dashboard instan\n\nDengan cara ini rekap yang biasanya 1 jam bisa selesai 15 menit.", ['excel', 'produktivitas', 'inventory'], 1],
            ['lesson-learned', 'Pelajaran dari Kesalahan Forecast Saat Lebaran', "Tahun lalu kami mengalami out-of-stock untuk kategori minuman dan kue kering H-7 Lebaran. Penyebab utamanya: forecast hanya berdasarkan data tahun sebelumnya tanpa mempertimbangkan pergeseran tanggal dan promo kompetitor.\n\nPerbaikan yang kami lakukan:\n- Menggabungkan data 3 tahun dengan penyesuaian kalender hijriah\n- Kolaborasi mingguan dengan tim marketing terkait rencana promo\n- Buffer stock 15% untuk top 50 SKU musiman", ['forecast', 'supply-chain', 'lebaran'], 2],
            ['innovation-ideas', 'Ide: Digital Shelf Label untuk Update Harga Real-time', "Saat ini perubahan harga promo memerlukan penggantian label manual yang memakan waktu ±3 jam per toko setiap minggu, dan sering terjadi selisih harga rak vs kasir.\n\nUsulan saya: pilot Electronic Shelf Label (ESL) di 2 toko flagship. Harga terhubung langsung ke sistem POS sehingga tidak ada lagi selisih. Estimasi penghematan 150 jam kerja/bulan per toko.", ['inovasi', 'digital', 'pricing'], 3],
            ['document-sop', 'SOP Penerimaan Barang di Gudang Toko (Update 2026)', "Ringkasan SOP terbaru penerimaan barang:\n\n1. Verifikasi surat jalan dengan PO di sistem\n2. Cek fisik: jumlah koli, kondisi kemasan, dan tanggal kedaluwarsa (minimal 2/3 shelf life)\n3. Scan barcode setiap item ke handheld\n4. Tandatangani BAST dan foto barang rusak (jika ada)\n5. Input GR maksimal 2 jam setelah barang diterima\n\nDokumen lengkap terlampir.", ['sop', 'gudang', 'receiving'], 4],
            ['success-story', 'Dari Kasir Menjadi Store Manager: Perjalanan 7 Tahun Saya', "Saya bergabung tahun 2019 sebagai kasir paruh waktu. Kuncinya: selalu bertanya, mengambil tanggung jawab lebih, dan aktif mengikuti program pelatihan internal.\n\nTahun ke-3 saya dipercaya menjadi Crew Leader, lalu Assistant Store Manager, dan kini memimpin toko dengan 45 orang tim. Terima kasih untuk semua mentor yang sudah membimbing. Jangan pernah ragu untuk bertumbuh!", ['career', 'inspirasi', 'journey'], 5],
            ['group-discussion', 'Diskusi: Bagaimana Cara Terbaik Menangani Komplain Pelanggan di Media Sosial?', "Belakangan komplain pelanggan semakin banyak masuk lewat Instagram dan TikTok. Bagaimana pengalaman teman-teman di toko masing-masing?\n\nApakah sebaiknya dijawab langsung oleh tim toko atau dieskalasi ke tim CS pusat? Berapa SLA ideal untuk membalas? Yuk berbagi pendapat di kolom komentar!", ['customer-service', 'social-media', 'diskusi'], 6],
            ['video-photo', 'Display Kemerdekaan Toko Bandung – Juara 1 Lomba Visual Merchandising', "Berikut dokumentasi display tema kemerdekaan yang kami buat menggunakan bahan daur ulang dari kardus dan botol bekas. Total biaya hanya Rp350.000 namun berhasil meningkatkan penjualan kategori snack 18% selama periode Agustus.\n\nTerima kasih untuk tim crew yang kreatif!", ['visual-merchandising', 'display', 'kreatif'], 7],
            ['best-practice', 'Briefing Pagi 10 Menit yang Efektif', "Format briefing pagi yang kami gunakan:\n\n• 2 menit: pencapaian kemarin (sales, NPS)\n• 3 menit: fokus hari ini (promo, SKU prioritas)\n• 3 menit: apresiasi crew terbaik\n• 2 menit: safety reminder\n\nBriefing jadi singkat, energik, dan semua crew paham target harian.", ['leadership', 'briefing', 'tim'], 8],
            ['tips-trick', 'Tips Menata Planogram agar Produk Fast Moving Mudah Dijangkau', "Prinsip yang selalu kami pakai: eye level is buy level. Produk margin tinggi di rak setinggi mata, produk fast moving di rak tangan (waist level) agar mudah diambil, dan produk besar/berat di rak bawah.\n\nJangan lupa terapkan FIFO/FEFO saat restock!", ['planogram', 'merchandising'], 9],
            ['lesson-learned', 'Belajar dari Insiden Listrik Padam 6 Jam di Toko', "Bulan lalu toko kami mengalami pemadaman listrik selama 6 jam. Beberapa hal yang kami pelajari:\n\n- Genset harus diuji mingguan, bukan bulanan\n- Siapkan prosedur transaksi offline & nota manual\n- Prioritaskan chiller dan freezer: tutup rapat, catat suhu tiap 30 menit\n\nSekarang kami punya checklist darurat yang ditempel di ruang kantor toko.", ['business-continuity', 'safety'], 10],
            ['innovation-ideas', 'Usulan Program "Buddy System" untuk Karyawan Baru', "Turnover karyawan baru di 3 bulan pertama cukup tinggi. Saya mengusulkan setiap karyawan baru didampingi satu 'buddy' (karyawan senior) selama 30 hari pertama dengan checklist onboarding yang jelas. Buddy mendapatkan apresiasi berupa poin GrowHub dan sertifikat.", ['onboarding', 'hr', 'retention'], 11],
            ['success-story', 'Tim Gudang Surabaya Capai Zero Discrepancy 6 Bulan Berturut-turut', "Kami ingin berbagi kebahagiaan: tim gudang DC Surabaya berhasil mencapai zero discrepancy pada stock opname selama 6 bulan berturut-turut! Kuncinya adalah cycle count harian, disiplin scan, dan budaya saling mengingatkan.", ['warehouse', 'achievement'], 12],
            ['tips-trick', 'Cara Cepat Membuat Konten Promo Toko dengan Canva', "Untuk tim toko yang ingin membuat poster promo sendiri: gunakan template brand kit yang sudah disediakan tim marketing di Canva, cukup ganti foto produk dan harga. Pastikan selalu mencantumkan periode promo dan syarat & ketentuan.", ['marketing', 'canva', 'promo'], 13],
            ['document-sop', 'Panduan Penanganan Barang Rusak & Retur ke Supplier', "Langkah-langkah:\n1. Pisahkan barang rusak ke area karantina\n2. Foto & catat di form retur digital\n3. Koordinasikan dengan supplier maksimal H+3\n4. Lakukan write-off jika tidak dapat diretur sesuai approval berjenjang", ['retur', 'sop', 'supplier'], 14],
            ['group-discussion', 'Shift 3 Hari Kerja 12 Jam vs 5 Hari 8 Jam – Mana yang Lebih Produktif?', "Beberapa toko mulai mencoba pola shift baru. Saya ingin mendengar perspektif teman-teman mengenai produktivitas, kelelahan, dan work-life balance dari masing-masing pola.", ['shift', 'wellbeing', 'diskusi'], 15],
        ];

        $comments = [
            'Terima kasih sudah berbagi, sangat bermanfaat! 🙏',
            'Ini akan langsung saya terapkan di toko kami.',
            'Insight yang keren. Apakah ada template yang bisa dibagikan?',
            'Setuju banget, kami juga mengalami hal serupa.',
            'Mantap! Semoga bisa jadi standar nasional.',
            'Boleh share data before-after-nya?',
            'Inspiratif sekali, terus berkarya!',
            'Kami pernah coba dan hasilnya positif juga.',
        ];

        $tx = [];
        $now = now();

        foreach ($samples as $i => [$catSlug, $title, $body, $tags, $authorIdx]) {
            $author = $employees[$authorIdx % $employees->count()];
            $publishedAt = $now->copy()->subDays(mt_rand(0, 40))->subHours(mt_rand(1, 20));
            $reviewer = $reviewers->random();

            $content = Content::create([
                'user_id' => $author->id,
                'category_id' => $categories[$catSlug]->id,
                'community_id' => mt_rand(0, 1) ? $communities->random()->id : null,
                'title' => $title,
                'body' => $body,
                'tags' => $tags,
                'status' => Content::STATUS_PUBLISHED,
                'reviewer_id' => $reviewer->id,
                'reviewed_at' => $publishedAt,
                'published_at' => $publishedAt,
                'views_count' => mt_rand(40, 600),
                'created_at' => $publishedAt->copy()->subHours(6),
                'updated_at' => $publishedAt,
            ]);

            if ($seedImages->isNotEmpty() && in_array($catSlug, ['video-photo', 'best-practice', 'success-story', 'innovation-ideas'])) {
                $content->media()->create([
                    'type' => 'image',
                    'path' => $seedImages[$i % $seedImages->count()],
                    'original_name' => basename($seedImages[$i % $seedImages->count()]),
                    'mime' => 'image/png',
                ]);
            }

            $tx[] = $this->tx($author->id, $points['content_submitted'], 'content_submitted', "Mengunggah konten \"$title\"", $content, $publishedAt->copy()->subHours(6));
            $tx[] = $this->tx($author->id, $points['content_published'], 'content_published', "Konten \"$title\" dipublikasikan", $content, $publishedAt);
            $tx[] = $this->tx($reviewer->id, $points['review_done'], 'review_done', "Mereview \"$title\"", $content, $publishedAt);

            // Likes
            $likers = $users->where('id', '!=', $author->id)->random(mt_rand(3, 15));
            foreach ($likers as $liker) {
                $at = $this->between($publishedAt, $now);
                Like::create(['user_id' => $liker->id, 'content_id' => $content->id, 'created_at' => $at, 'updated_at' => $at]);
                $tx[] = $this->tx($author->id, $points['like_received'], 'like_received', "Konten kamu disukai (#{$liker->id})", $content, $at);
            }

            // Saves
            $savers = $users->where('id', '!=', $author->id)->random(mt_rand(0, 6));
            foreach ($savers as $saver) {
                $at = $this->between($publishedAt, $now);
                Save::create(['user_id' => $saver->id, 'content_id' => $content->id, 'created_at' => $at, 'updated_at' => $at]);
                $tx[] = $this->tx($author->id, $points['save_received'], 'save_received', "Konten kamu disimpan (#{$saver->id})", $content, $at);
            }

            // Comments
            $commenters = $users->where('id', '!=', $author->id)->random(mt_rand(1, 5));
            foreach ($commenters as $commenter) {
                $at = $this->between($publishedAt, $now);
                $c = Comment::create([
                    'content_id' => $content->id, 'user_id' => $commenter->id,
                    'body' => $comments[array_rand($comments)], 'created_at' => $at, 'updated_at' => $at,
                ]);
                $tx[] = $this->tx($commenter->id, $points['comment_given'], 'comment_given', "Berkomentar di \"$title\"", $content, $at);
                $tx[] = $this->tx($author->id, $points['comment_received'], 'comment_received', "{$commenter->name} mengomentari kontenmu", $content, $at);

                if (mt_rand(0, 2) === 0) {
                    $replyAt = $this->between($at, $now);
                    Comment::create([
                        'content_id' => $content->id, 'user_id' => $author->id, 'parent_id' => $c->id,
                        'body' => 'Terima kasih atas masukannya! 😊', 'created_at' => $replyAt, 'updated_at' => $replyAt,
                    ]);
                }
            }

            $content->update([
                'likes_count' => $likers->count(),
                'saves_count' => $savers->count(),
                'comments_count' => Comment::where('content_id', $content->id)->count(),
                'shares_count' => mt_rand(0, 12),
            ]);
        }

        // Konten menunggu review & ditolak (untuk demo alur reviewer)
        $pendingSamples = [
            ['innovation-ideas', 'Usulan Self-Checkout di Toko Format Express', "Dengan semakin banyaknya pelanggan yang berbelanja dalam jumlah kecil, self-checkout bisa mengurangi antrian dan memberikan pengalaman yang lebih cepat. Saya mengusulkan pilot di 3 toko format express dengan 2 mesin per toko.", ['self-checkout', 'inovasi'], 4],
            ['tips-trick', 'Trik Menyusun Jadwal Shift Adil dengan Google Sheets', "Saya membuat template Google Sheets yang otomatis menghitung total jam per crew dan memberi warna jika ada yang melebihi batas jam lembur. Template ini sudah dipakai 4 bulan di toko kami dan sangat membantu.", ['shift', 'template'], 7],
            ['lesson-learned', 'Kegagalan Program Membership Pilot dan Apa yang Kami Pelajari', "Program membership pilot kami di kuartal lalu hanya mencapai 30% dari target pendaftar. Evaluasi kami: benefit kurang jelas, proses daftar terlalu panjang, dan crew kurang dibekali script penawaran.", ['membership', 'evaluasi'], 1],
        ];
        foreach ($pendingSamples as [$catSlug, $title, $body, $tags, $authorIdx]) {
            $author = $employees[$authorIdx];
            $at = $now->copy()->subHours(mt_rand(2, 48));
            $content = Content::create([
                'user_id' => $author->id, 'category_id' => $categories[$catSlug]->id,
                'title' => $title, 'body' => $body, 'tags' => $tags, 'status' => Content::STATUS_PENDING,
                'created_at' => $at, 'updated_at' => $at,
            ]);
            $tx[] = $this->tx($author->id, $points['content_submitted'], 'content_submitted', "Mengunggah konten \"$title\"", $content, $at);
        }

        Content::create([
            'user_id' => $employees[0]->id, 'category_id' => $categories['tips-trick']->id,
            'title' => 'Promo Produk Pribadi', 'body' => 'Halo semua, saya menjual produk skincare dengan harga spesial, silakan hubungi saya.',
            'tags' => ['jualan'], 'status' => Content::STATUS_REJECTED, 'reviewer_id' => $reviewers[1]->id,
            'review_note' => 'Konten bersifat promosi pribadi dan tidak relevan dengan pengembangan perusahaan.',
            'reviewed_at' => $now->copy()->subDays(2),
        ]);
        Content::create([
            'user_id' => $employees[0]->id, 'category_id' => $categories['best-practice']->id,
            'title' => 'Checklist Opening Toko (Draft)', 'body' => 'Draft checklist opening toko yang masih saya lengkapi: cek kebersihan, cek kasir, cek suhu chiller ...',
            'tags' => ['checklist'], 'status' => Content::STATUS_DRAFT,
        ]);

        // ------------------------------------------------------------------
        // Learning Center
        // ------------------------------------------------------------------
        $courses = [
            ['Service Excellence 101', 'Customer Service', 'Beginner', 'Dasar-dasar pelayanan prima untuk seluruh karyawan front-liner.', '#1e8c45', 15, 20],
            ['Memahami Planogram & Visual Merchandising', 'Merchandising', 'Intermediate', 'Prinsip penataan produk yang meningkatkan penjualan.', '#8b5cf6', 20, 25],
            ['Inventory Control & Stock Opname', 'Supply Chain', 'Intermediate', 'Teknik menjaga akurasi stok dan mencegah shrinkage.', '#0891b2', 25, 25],
            ['Kepemimpinan untuk Crew Leader', 'Leadership', 'Advanced', 'Membangun tim yang solid, memberi feedback, dan coaching.', '#f59e0b', 30, 35],
            ['Keamanan & Keselamatan Kerja di Toko', 'K3', 'Beginner', 'Prosedur K3, penanganan darurat, dan pencegahan kecelakaan.', '#e11d48', 12, 15],
            ['Data-Driven Retail dengan Excel', 'Digital Skill', 'Intermediate', 'Analisis penjualan sederhana menggunakan Excel & Pivot.', '#142c14', 35, 30],
        ];
        foreach ($courses as [$title, $cat, $level, $summary, $color, $dur, $pts]) {
            Course::create([
                'title' => $title, 'slug' => Str::slug($title), 'category' => $cat, 'level' => $level,
                'summary' => $summary, 'cover_color' => $color, 'duration_minutes' => $dur, 'points_reward' => $pts,
                'body' => $this->courseBody($title, $summary),
            ]);
        }

        // ------------------------------------------------------------------
        // Aktivitas harian & streak (14 hari terakhir)
        // ------------------------------------------------------------------
        foreach ($users as $u) {
            $streak = mt_rand(0, 14);
            for ($d = $streak - 1; $d >= 0; $d--) {
                $tx[] = $this->tx($u->id, $points['daily_login'], 'daily_login', 'Aktivitas harian – streak hari ke-'.($streak - $d), null, $now->copy()->subDays($d)->setTime(8, mt_rand(0, 59)));
            }
            $u->forceFill([
                'current_streak' => $streak,
                'longest_streak' => max($streak, mt_rand($streak, 21)),
                'last_activity_date' => $streak > 0 ? today() : today()->subDays(3),
            ])->save();
        }

        foreach (array_chunk($tx, 500) as $chunk) {
            PointTransaction::insert($chunk);
        }

        // Sinkronkan total point
        foreach ($users as $u) {
            $u->forceFill(['points' => (int) PointTransaction::where('user_id', $u->id)->sum('points')])->save();
        }

        // Notifikasi contoh
        foreach ($users as $u) {
            AppNotification::create([
                'user_id' => $u->id, 'type' => 'system',
                'title' => 'Selamat datang di GrowHub! 🌱',
                'message' => 'Mulai bagikan pengetahuan dan pengalamanmu untuk mengumpulkan point dan naik level.',
                'link' => '/create',
            ]);
        }
    }

    private function tx(int $userId, int $points, string $action, string $desc, ?Content $ref, Carbon $at): array
    {
        return [
            'user_id' => $userId,
            'points' => $points,
            'action' => $action,
            'description' => $desc,
            'reference_type' => $ref ? $ref->getMorphClass() : null,
            'reference_id' => $ref?->id,
            'created_at' => $at,
            'updated_at' => $at,
        ];
    }

    private function between(Carbon $from, Carbon $to): Carbon
    {
        $a = $from->timestamp;
        $b = max($a + 60, $to->timestamp);

        return Carbon::createFromTimestamp(mt_rand($a, $b), config('app.timezone'));
    }

    private function courseBody(string $title, string $summary): string
    {
        return <<<MD
## Tentang Materi
$summary

## Tujuan Pembelajaran
- Memahami konsep utama dari materi **$title**
- Mampu menerapkan praktik terbaik dalam pekerjaan sehari-hari
- Mengidentifikasi peluang perbaikan di area kerja masing-masing

## Modul 1 – Konsep Dasar
Setiap proses kerja yang baik dimulai dari pemahaman tujuan. Pahami siapa pelanggan Anda (internal maupun eksternal), apa ekspektasi mereka, dan bagaimana pekerjaan Anda memberikan nilai tambah.

## Modul 2 – Praktik di Lapangan
Terapkan siklus **Plan – Do – Check – Act**: rencanakan perbaikan kecil, jalankan, ukur hasilnya, lalu standarkan jika berhasil. Dokumentasikan hasilnya dan bagikan di GrowHub agar rekan lain ikut belajar.

## Modul 3 – Refleksi
Tuliskan satu hal yang akan Anda terapkan minggu ini. Bagikan pengalaman Anda sebagai konten **Lesson Learned** atau **Best Practice** untuk mendapatkan point tambahan!
MD;
    }
}
