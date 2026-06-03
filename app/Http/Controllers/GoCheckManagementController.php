<?php

namespace App\Http\Controllers;

use App\Models\FiveRBagianAssignment;
use App\Models\GoCheck;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GoCheckManagementController extends Controller
{
    /**
     * Decode kolom foto JSON (array path) atau path tunggal.
     */
    private function decodePhotoPaths(?string $value): array
    {
        if (empty($value)) {
            return [];
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return array_values(array_filter($decoded));
        }

        return [$value];
    }

    private const BAGIAN_OPTIONS = [
        'Bagian Mekanik & Electrical',
        'Bagian Pemastian Operasional',
        'Bagian Pemenuhan Regulasi',
        'Bagian Pendukung Teknis',
        'Bagian Pengadaan Barang Operasional',
        'Bagian Pengawasan Mutu',
        'Bagian Pengemasan Farma',
        'Bagian Pengendalian Proses Produksi',
        'Bagian Pengendalian Sistem',
        'Bagian Penyimpanan',
        'Bagian Produksi I',
        'Bagian Produksi II',
        'Bagian Produksi III',
        'Bagian SDM & Akuntansi',
        'Bagian Umum dan K3L',
        'Bagian Utility',
        'IT Support Plant',
        'Lainnya',
    ];

    public function dashboard()
    {
        return Inertia::render('GoCheck/Management/Dashboard', [
            'stats' => [
                // Temuan Finder sudah masuk, Solver belum input perbaikan
                'waiting_solver' => GoCheck::query()
                    ->where('status_perbaikan', 'pending')
                    ->where(function ($q) {
                        $q->whereNull('approval_status')
                            ->orWhereNotIn('approval_status', ['APPROVED', 'REJECTED']);
                    })
                    ->count(),
                // Solver sudah selesai, menunggu approve/reject manajemen
                'pending' => GoCheck::query()
                    ->where('status_perbaikan', 'selesai')
                    ->where(function ($q) {
                        $q->where('approval_status', 'PENDING')
                            ->orWhereNull('approval_status');
                    })
                    ->count(),
                'approved' => GoCheck::where('approval_status', 'APPROVED')->count(),
                'rejected' => GoCheck::where('approval_status', 'REJECTED')->count(),
                'total' => GoCheck::count(),
                'team_count' => User::where('role', 'five_r_team')->count(),
            ],
        ]);
    }

    public function teamIndex()
    {
        $teamMembers = User::whereIn('role', ['five_r_team', 'five_r_ketua', 'five_r_sekretaris'])
            ->orderBy('name')
            ->get(['id', 'name', 'npp', 'role', 'bagian']);

        try {
            $assignments = FiveRBagianAssignment::query()
                ->get()
                ->groupBy('user_id')
                ->map(fn ($rows, $userId) => [
                    'user_id' => (int) $userId,
                    'bagian' => $rows->pluck('bagian')->values()->all(),
                ])
                ->values()
                ->all();
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('go_check.management.dashboard')
                ->with('error', 'Tabel penugasan tim belum tersedia. Jalankan migrasi database (php artisan migrate) di server.');
        }

        return Inertia::render('GoCheck/Management/Team', [
            'teamMembers' => $teamMembers,
            'assignments' => $assignments,
            'bagianOptions' => self::BAGIAN_OPTIONS,
            'roleOptions' => [
                ['value' => 'five_r_team', 'label' => 'Tim 5R (Finder)'],
                ['value' => 'five_r_ketua', 'label' => 'Ketua 5R'],
                ['value' => 'five_r_sekretaris', 'label' => 'Sekretaris 5R'],
                ['value' => 'user', 'label' => 'User biasa'],
            ],
        ]);
    }

    public function updateMemberRole(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:five_r_team,five_r_ketua,five_r_sekretaris,user',
        ]);

        $user = User::findOrFail($validated['user_id']);

        if ($user->isAdmin()) {
            return back()->with('error', 'Role admin tidak dapat diubah dari sini.');
        }

        $user->update(['role' => $validated['role']]);

        if ($validated['role'] !== 'five_r_team') {
            FiveRBagianAssignment::where('user_id', $user->id)->delete();
        }

        return back()->with('success', 'Role '.$user->name.' diperbarui.');
    }

    public function syncAssignments(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'bagian' => 'required|array',
            'bagian.*' => 'string|max:255',
        ]);

        $user = User::findOrFail($validated['user_id']);

        if (! $user->canActAsGoCheckFinder() && ! $user->isFiveRKetua() && ! $user->isFiveRSekretaris()) {
            return back()->with('error', 'Penugasan bagian hanya untuk tim 5R (finder). Set role ke Tim 5R terlebih dahulu.');
        }

        FiveRBagianAssignment::where('user_id', $user->id)->delete();

        foreach (array_unique($validated['bagian']) as $bagian) {
            if ($bagian === '') {
                continue;
            }
            FiveRBagianAssignment::create([
                'user_id' => $user->id,
                'bagian' => $bagian,
                'assigned_by' => Auth::user()->id,
            ]);
        }

        return back()->with('success', 'Penugasan bagian untuk '.$user->name.' disimpan.');
    }

    public function goCheckIndex(Request $request)
    {
        $query = GoCheck::with(['finder:id,name,npp', 'solver:id,name,npp'])
            ->latest();

        if ($request->filled('approval_status')) {
            $status = $request->approval_status;
            if ($status === 'MENUNGGU_SOLVER') {
                $query->where(function ($q) {
                    $q->whereNull('approval_status')
                        ->where('status_perbaikan', 'pending');
                });
            } elseif ($status === 'PENDING') {
                $query->where(function ($q) {
                    $q->where('approval_status', 'PENDING')
                        ->orWhere(function ($q2) {
                            $q2->whereNull('approval_status')
                                ->where('status_perbaikan', 'selesai');
                        });
                });
            } else {
                $query->where('approval_status', $status);
            }
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('bagian', 'like', "%{$s}%")
                    ->orWhere('ruangan_temuan', 'like', "%{$s}%")
                    ->orWhere('area_temuan', 'like', "%{$s}%");
            });
        }

        $goChecks = $query->paginate(15)->withQueryString();

        $goChecks->getCollection()->transform(function ($row) {
            $photos = $this->decodePhotoPaths($row->photo_temuan);
            $fotoPerbaikan = $this->decodePhotoPaths($row->foto_perbaikan);
            $approvalStatus = $row->approval_status;
            $statusPerbaikan = $row->status_perbaikan ?? 'pending';
            $canApproveReject = $statusPerbaikan === 'selesai'
                && in_array($approvalStatus, [null, 'PENDING'], true);

            return [
                'id' => $row->id,
                'bagian' => $row->bagian,
                'area_temuan' => $row->area_temuan,
                'ruangan_temuan' => $row->ruangan_temuan,
                'penjelasan_temuan' => $row->penjelasan_temuan,
                'pic_terkait' => $row->pic_terkait,
                'photo_temuan_urls' => collect($photos)->map(fn ($p) => asset('storage/'.$p))->values()->all(),
                'status' => $row->status,
                'status_perbaikan' => $statusPerbaikan,
                'approval_status' => $approvalStatus ?? ($statusPerbaikan === 'selesai' ? 'PENDING' : 'MENUNGGU_SOLVER'),
                'can_approve_reject' => $canApproveReject,
                'reject_comment' => $row->reject_comment,
                'keterangan_perbaikan' => $row->keterangan_perbaikan,
                'foto_perbaikan_urls' => collect($fotoPerbaikan)->map(fn ($p) => asset('storage/'.$p))->values()->all(),
                'tanggal_perbaikan' => $row->tanggal_perbaikan?->format('d/m/Y H:i'),
                'created_at' => $row->created_at->format('d/m/Y H:i'),
                'finder_name' => $row->finder?->name,
                'finder_npp' => $row->finder?->npp,
                'solver_name' => $row->solver?->name,
                'solver_npp' => $row->solver?->npp,
            ];
        });

        return Inertia::render('GoCheck/Management/Index', [
            'goChecks' => $goChecks,
            'filters' => [
                'search' => $request->search ?? '',
                'approval_status' => $request->approval_status ?? '',
            ],
        ]);
    }

    public function approve($id)
    {
        $goCheck = GoCheck::with(['finder', 'solver'])->findOrFail($id);

        if ($goCheck->approval_status === 'APPROVED') {
            return back()->with('success', 'Go Check ini sudah di-approve.');
        }

        if (($goCheck->status_perbaikan ?? 'pending') !== 'selesai') {
            return back()->with('error', 'Solver belum menginput perbaikan. Tombol Approve/Reject aktif setelah Solver selesai.');
        }

        if ($goCheck->approval_status === 'REJECTED') {
            return back()->with('error', 'Go Check sudah di-reject sebelumnya.');
        }

        $goCheck->update(GoCheck::approvedAttributes(Auth::user()->id));

        if ($goCheck->finder) {
            $goCheck->finder->increment('points_balance', 10);
            Notification::create([
                'user_id' => $goCheck->finder_user_id,
                'go_check_id' => $goCheck->id,
                'type' => 'go_check_approved_finder',
                'title' => 'Go Check — Temuan di-approve (+10 poin)',
                'message' => 'Temuan Go Check Anda di '.$goCheck->bagian.' telah di-approve. Anda mendapat 10 poin sebagai Finder.',
            ]);
        }

        if ($goCheck->solver) {
            $goCheck->solver->increment('points_balance', 10);
            Notification::create([
                'user_id' => $goCheck->solver_user_id,
                'go_check_id' => $goCheck->id,
                'type' => 'go_check_approved_solver',
                'title' => 'Go Check — Perbaikan di-approve (+10 poin)',
                'message' => 'Perbaikan Anda sebagai Solver telah di-approve. Anda mendapat 10 poin.',
            ]);
        }

        return back()->with('success', 'Go Check di-approve. Poin Finder & Solver diberikan.');
    }

    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'reject_comment' => 'required|string|max:1000',
        ]);

        $goCheck = GoCheck::with(['finder', 'solver'])->findOrFail($id);

        $goCheck->update(GoCheck::rejectedAttributes(Auth::user()->id, $validated['reject_comment']));

        foreach ([$goCheck->finder, $goCheck->solver] as $recipient) {
            if ($recipient) {
                Notification::create([
                    'user_id' => $recipient->id,
                    'go_check_id' => $goCheck->id,
                    'type' => 'go_check_rejected',
                    'title' => 'Go Check — Tidak di-approve',
                    'message' => 'Go Check ditolak manajemen. Catatan: '.$validated['reject_comment'],
                ]);
            }
        }

        return back()->with('success', 'Go Check di-reject. Notifikasi terkirim.');
    }
}
