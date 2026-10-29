<?php

/*
|--------------------------------------------------------------------------
| GrowHub Gamification Rules
|--------------------------------------------------------------------------
| Semua aturan point, streak dan level terpusat di sini sehingga mudah
| disesuaikan tanpa mengubah kode.
*/

return [

    'points' => [
        'daily_login' => 2,          // aktivitas harian pertama (menjaga streak)
        'content_submitted' => 5,    // mengunggah konten untuk direview
        'content_published' => 25,   // konten disetujui reviewer
        'like_received' => 2,        // konten mendapatkan like
        'comment_given' => 1,        // memberi komentar
        'comment_received' => 3,     // konten mendapatkan komentar
        'share_given' => 1,          // membagikan konten
        'share_received' => 2,       // konten dibagikan orang lain
        'save_received' => 2,        // konten disimpan orang lain
        'review_done' => 3,          // reviewer menyelesaikan review
    ],

    // Bonus streak: setiap kelipatan N hari berturut-turut
    'streak_bonus' => [
        'every_days' => 7,
        'points' => 15,
    ],

    // Batas harian agar tidak di-spam (jumlah aksi yang mendapat point per hari)
    'daily_caps' => [
        'comment_given' => 10,
        'share_given' => 10,
    ],

    'levels' => [
        ['level' => 1, 'name' => 'Seedling',   'min' => 0],
        ['level' => 2, 'name' => 'Sprout',     'min' => 100],
        ['level' => 3, 'name' => 'Sapling',    'min' => 250],
        ['level' => 4, 'name' => 'Grower',     'min' => 500],
        ['level' => 5, 'name' => 'Cultivator', 'min' => 900],
        ['level' => 6, 'name' => 'Harvester',  'min' => 1400],
        ['level' => 7, 'name' => 'Mentor',     'min' => 2000],
        ['level' => 8, 'name' => 'Evergreen',  'min' => 3000],
    ],
];
