<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * @return array{team_name: string|null, scheduled_date: string|null, target_area: string|null}|null
     */
    private function resolveGoCheckSchedulePayload(Notification $notification, $schedule): ?array
    {
        if ($schedule) {
            return [
                'id' => $schedule->id,
                'team_name' => $schedule->team?->inspector_area,
                'scheduled_date' => $schedule->scheduled_date->format('d/m/Y'),
                'target_area' => $schedule->target_area,
                'bagian' => $schedule->bagian,
                'notes' => $schedule->notes,
                'status' => $schedule->status,
            ];
        }

        if ($notification->type !== 'go_check_schedule') {
            return null;
        }

        return $this->parseScheduleFromMessage($notification->message);
    }

    /**
     * @return array{team_name: string|null, scheduled_date: string|null, target_area: string|null}|null
     */
    private function parseScheduleFromMessage(?string $message): ?array
    {
        if (! $message) {
            return null;
        }

        if (preg_match(
            '/Anda dijadwalkan Go Check pada (\d{2}\/\d{2}\/\d{4}) untuk area (.+?) \(tim: (.+?)\)\.?$/u',
            $message,
            $m
        )) {
            return [
                'team_name' => trim($m[3]),
                'scheduled_date' => trim($m[1]),
                'target_area' => trim($m[2]),
            ];
        }

        if (preg_match(
            '/Hari ini \((\d{2}\/\d{2}\/\d{4})\) tim Anda wajib melakukan Go Check di (.+?) \(area asal: (.+?)\)\.?$/u',
            $message,
            $m
        )) {
            return [
                'team_name' => trim($m[3]),
                'scheduled_date' => trim($m[1]),
                'target_area' => trim($m[2]),
            ];
        }

        return null;
    }

    /**
     * Tampilkan semua notifikasi user.
     */
    public function index()
    {
        $user = Auth::user();
        
        $notifications = Notification::where('user_id', $user->id)
            ->with([
                'goBoost.user',
                'goBoost.mentionedUser',
                'goCare.user',
                'goCheck.finder',
                'goCheck.solver',
                'goCheckSchedule.team:id,inspector_area',
            ])
            ->latest()
            ->paginate(20)
            ->through(function ($notification) use ($user) {
                $goBoost = $notification->goBoost;
                $isMentioned = $goBoost && (int) $goBoost->mentioned_user_id === (int) $user->id;
                $goCheck = $notification->goCheck;
                $canSubmitGoCheckSolver = $goCheck
                    && $notification->type === 'go_check_solver_needed'
                    && ($goCheck->status_perbaikan ?? 'pending') !== 'selesai'
                    && (int) $goCheck->finder_user_id !== (int) $user->id
                    && (
                        ($goCheck->solver_user_id && (int) $goCheck->solver_user_id === (int) $user->id)
                        || (! $goCheck->solver_user_id && ($user->bagian ?? '') === $goCheck->bagian)
                    );
                $hasPerbaikan = $goBoost && !empty($goBoost->keterangan_perbaikan);
                $goCare = $notification->goCare;
                $schedule = $notification->goCheckSchedule;

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
                    'go_care' => $goCare ? [
                        'id' => $goCare->id,
                        'bagian' => $goCare->bagian ?? $goCare->bagian_temuan,
                        'bagian_temuan' => $goCare->bagian_temuan,
                        'area_temuan' => $goCare->area_temuan ?? null,
                        'user_name' => $goCare->user->name ?? ($goCare->nama_karyawan ?? 'N/A'),
                        'approval_status' => $goCare->approval_status ?? 'PENDING',
                        'created_at' => $goCare->created_at->format('d/m/Y H:i'),
                    ] : null,
                    'go_check' => $goCheck ? [
                        'id' => $goCheck->id,
                        'bagian' => $goCheck->bagian,
                        'area_temuan' => $goCheck->area_temuan,
                        'ruangan_temuan' => $goCheck->ruangan_temuan,
                        'penjelasan_temuan' => $goCheck->penjelasan_temuan,
                        'finder_name' => $goCheck->finder?->name ?? 'N/A',
                        'can_submit_solver' => $canSubmitGoCheckSolver,
                        'has_perbaikan' => ! empty($goCheck->keterangan_perbaikan),
                        'keterangan_perbaikan' => $goCheck->keterangan_perbaikan,
                        'foto_perbaikan' => $goCheck->foto_perbaikan ? json_decode($goCheck->foto_perbaikan, true) : null,
                        'status_perbaikan' => $goCheck->status_perbaikan ?? 'pending',
                        'tanggal_perbaikan' => $goCheck->tanggal_perbaikan
                            ? (is_string($goCheck->tanggal_perbaikan)
                                ? \Carbon\Carbon::parse($goCheck->tanggal_perbaikan)->format('d/m/Y H:i')
                                : $goCheck->tanggal_perbaikan->format('d/m/Y H:i'))
                            : null,
                    ] : null,
                    'go_check_schedule' => $this->resolveGoCheckSchedulePayload($notification, $schedule),
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
