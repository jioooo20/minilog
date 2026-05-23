<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Daftar notifikasi user yang sedang login
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Notification::where('user_id', $user->id)
            ->with('incident')
            ->orderByDesc('created_at');

        // Filter read/unread
        $readFilter = $request->query('read_filter');
        if ($readFilter === 'unread') {
            $query->where('is_read', false);
        } elseif ($readFilter === 'read') {
            $query->where('is_read', true);
        }

        // Filter type
        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        $perPage = $request->query('per_page', 20);
        $notifications = $query->paginate($perPage);

        // Statistik
        $stats = [
            'unread_count' => Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count(),
            'total_count' => Notification::where('user_id', $user->id)->count(),
            'today_count' => Notification::where('user_id', $user->id)
                ->whereDate('created_at', today())
                ->count(),
        ];

        // Ensure the resource collection is fully serialized to plain arrays
        $payload = NotificationResource::collection($notifications)->response()->getData(true);

        if ($request->wantsJson()) {
            return response()->json([
                'data' => $payload['data'] ?? $payload,
                'stats' => $stats,
            ]);
        }

        // For normal (Inertia) requests, render the Inertia page with props
        return Inertia::render('Notifications/Index', [
            'notifications' => $payload,
            'stats' => $stats,
        ]);
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca
     */
    public function markAsRead(Request $request, Notification $notification)
    {
        // Pastikan notifikasi milik user yang sedang login
        if ($notification->user_id !== $request->user()->id) {
            abort(403, 'Notifikasi bukan milik Anda.');
        }

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json([
            'message' => 'Notifikasi ditandai sudah dibaca.',
            'notification' => new NotificationResource($notification->fresh()),
        ]);
    }

    /**
     * Tandai semua notifikasi user sebagai sudah dibaca
     */
    public function markAllAsRead(Request $request)
    {
        $updated = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'message' => "{$updated} notifikasi ditandai sudah dibaca.",
            'updated_count' => $updated,
        ]);
    }

    /**
     * Hapus notifikasi (opsional)
     */
    public function destroy(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            abort(403, 'Notifikasi bukan milik Anda.');
        }

        $notification->delete();

        return response()->json(['message' => 'Notifikasi dihapus.']);
    }

    /**
     * Hapus semua notifikasi yang sudah dibaca
     */
    public function destroyRead(Request $request)
    {
        $deleted = Notification::where('user_id', $request->user()->id)
            ->where('is_read', true)
            ->delete();

        return response()->json([
            'message' => "{$deleted} notifikasi terhapus.",
            'deleted_count' => $deleted,
        ]);
    }
}