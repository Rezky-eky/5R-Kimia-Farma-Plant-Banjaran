<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * Tampilkan semua notifikasi user.
     */
    public function index()
    {
        $user = Auth::user();
        
        $notifications = Notification::where('user_id', $user->id)
            ->with('goBoost.user', 'goBoost.mentionedUser')
            ->latest()
            ->paginate(20)
            ->through(function ($notification) use ($user) {
                $goBoost = $notification->goBoost;
                $isMentioned = $goBoost && $goBoost->mentioned_user_id === $user->id;
                $hasPerbaikan = $goBoost && !empty($goBoost->keterangan_perbaikan);

                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'is_read' => $notification->is_read,
                    'created_at' => $notification->created_at->format('d/m/Y H:i'),
                    'created_at_human' => $notification->created_at->diffForHumans(),
                    'go_boost' => $goBoost ? [
                        'id' => $goBoost->id,
                        'area_temuan' => $goBoost->area_temuan,
                        'ruangan_temuan' => $goBoost->ruangan_temuan,
                        'penjelasan_temuan' => $goBoost->penjelasan_temuan,
                        'user_name' => $goBoost->user->name ?? 'N/A',
                        'mentioned_user_id' => $goBoost->mentioned_user_id,
                        'is_mentioned' => $isMentioned,
                        'has_perbaikan' => $hasPerbaikan,
                        'keterangan_perbaikan' => $goBoost->keterangan_perbaikan,
                        'foto_perbaikan' => $goBoost->foto_perbaikan ? json_decode($goBoost->foto_perbaikan, true) : null,
                        'status_perbaikan' => $goBoost->status_perbaikan ?? 'pending',
                        'tanggal_perbaikan' => $goBoost->tanggal_perbaikan 
                            ? (is_string($goBoost->tanggal_perbaikan) 
                                ? \Carbon\Carbon::parse($goBoost->tanggal_perbaikan)->format('d/m/Y H:i')
                                : $goBoost->tanggal_perbaikan->format('d/m/Y H:i'))
                            : null,
                    ] : null,
                ];
            });

        return Inertia::render('Notification/Index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Tandai notifikasi sebagai sudah dibaca.
     */
    public function markAsRead($id)
    {
        $userId = Auth::user()->id;
        $notification = Notification::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $notification->update(['is_read' => true]);

        return back()->with('success', 'Notifikasi ditandai sebagai sudah dibaca.');
    }

    /**
     * Tandai semua notifikasi sebagai sudah dibaca.
     */
    public function markAllAsRead()
    {
        $userId = Auth::user()->id;
        Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back()->with('success', 'Semua notifikasi ditandai sebagai sudah dibaca.');
    }

    /**
     * Hapus notifikasi.
     */
    public function destroy($id)
    {
        $userId = Auth::user()->id;
        $notification = Notification::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $notification->delete();

        return back()->with('success', 'Notifikasi dihapus.');
    }
}
