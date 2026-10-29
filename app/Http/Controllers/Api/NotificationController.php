<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $q = AppNotification::where('user_id', $request->user()->id)
            ->with('actor:id,name,avatar')
            ->latest();

        if ($request->query('filter') === 'unread') {
            $q->whereNull('read_at');
        }

        return response()->json([
            'data' => $q->paginate(20),
            'unread' => AppNotification::where('user_id', $request->user()->id)->whereNull('read_at')->count(),
        ]);
    }

    public function unreadCount(Request $request)
    {
        return response()->json([
            'unread' => AppNotification::where('user_id', $request->user()->id)->whereNull('read_at')->count(),
        ]);
    }

    public function markRead(Request $request, AppNotification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request)
    {
        AppNotification::where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
