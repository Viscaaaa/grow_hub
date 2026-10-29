<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function faqs()
    {
        $p = config('gamification.points');

        return [
            ['group' => 'Akun', 'q' => 'Bagaimana cara login ke GrowHub?', 'a' => 'Gunakan NIP (9-10 digit) sebagai username. Password awal sama dengan NIP Anda. Saat pertama kali login, Anda akan langsung diarahkan untuk mengganti password baru demi keamanan.'],
            ['group' => 'Akun', 'q' => 'Saya lupa password, apa yang harus dilakukan?', 'a' => 'Kirim tiket bantuan atau hubungi tim Administrator HC/IT. Reset password hanya dapat dilakukan oleh Administrator. Setelah direset, Anda dapat login kembali dan mengganti password baru.'],
            ['group' => 'Konten', 'q' => 'Konten apa saja yang bisa saya bagikan?', 'a' => 'Best Practice, Tips & Trick, Lesson Learned, Innovation & Ideas, Dokumen/SOP, Success Story / Employee Journey, Group Discussion, serta Video & Foto Sharing.'],
            ['group' => 'Konten', 'q' => 'Mengapa konten saya belum muncul di Knowledge Feed?', 'a' => 'Setiap konten akan direview oleh tim reviewer untuk memastikan relevansi dan kepatuhan. Status dapat dipantau di menu My Contribution.'],
            ['group' => 'Konten', 'q' => 'Konten saya ditolak, apa yang harus dilakukan?', 'a' => 'Buka My Contribution, baca catatan reviewer, perbaiki konten lalu kirim ulang untuk direview.'],
            ['group' => 'Konten', 'q' => 'Format dan ukuran file apa yang didukung?', 'a' => 'Gambar (JPG, PNG, GIF, WEBP), video (MP4, MOV, WEBM), dan dokumen (PDF, Word, Excel, PowerPoint, TXT). Maksimal 10 file, masing-masing 50 MB.'],
            ['group' => 'Gamifikasi', 'q' => 'Bagaimana cara mendapatkan point?', 'a' => "Aktivitas harian +{$p['daily_login']}, unggah konten +{$p['content_submitted']}, konten dipublikasikan +{$p['content_published']}, menerima like +{$p['like_received']}, menerima komentar +{$p['comment_received']}, berkomentar +{$p['comment_given']}, membagikan konten +{$p['share_given']}, serta menyelesaikan materi Learning Center."],
            ['group' => 'Gamifikasi', 'q' => 'Apa itu streak?', 'a' => 'Streak adalah jumlah hari berturut-turut Anda aktif di GrowHub. Setiap kelipatan 7 hari Anda mendapatkan bonus point. Streak akan kembali ke 1 jika Anda melewatkan satu hari.'],
            ['group' => 'Gamifikasi', 'q' => 'Bagaimana level dihitung?', 'a' => 'Level ditentukan dari total point: Seedling, Sprout, Sapling, Grower, Cultivator, Harvester, Mentor, hingga Evergreen.'],
            ['group' => 'Gamifikasi', 'q' => 'Bagaimana leaderboard bekerja?', 'a' => 'Leaderboard menampilkan peringkat berdasarkan point yang dikumpulkan minggu ini, bulan ini, atau sepanjang waktu, dan dapat difilter per departemen.'],
        ];
    }

    public function index(Request $request)
    {
        return SupportTicket::where('user_id', $request->user()->id)->latest()->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'category' => ['required', 'in:general,account,content,technical,suggestion'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $ticket = SupportTicket::create([...$data, 'user_id' => $request->user()->id]);

        return response()->json(['message' => 'Tiket bantuan terkirim. Tim kami akan segera merespons.', 'ticket' => $ticket], 201);
    }
}
