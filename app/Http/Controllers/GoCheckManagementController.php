<?php

namespace App\Http\Controllers;

use App\Models\FiveRBagianAssignment;
use App\Models\FiveRTeam;
use App\Models\FiveRTeamAuditTarget;
use App\Models\FiveRTeamMember;
use App\Models\GoCheck;
use App\Models\GoCheckSchedule;
use App\Models\Notification;
use App\Models\User;
use App\Services\GoCheckTeamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class GoCheckManagementController extends Controller
{
    public function __construct(
        private GoCheckTeamService $teamService,
    ) {}

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
        $teamCount = 0;
        $upcomingSchedules = 0;
        $teamsMigrationPending = ! Schema::hasTable('five_r_teams');

        if (! $teamsMigrationPending) {
            $teamCount = FiveRTeamMember::query()->distinct()->count('user_id');
            $upcomingSchedules = GoCheckSchedule::where('status', 'scheduled')
                ->where('scheduled_date', '>=', now()->toDateString())
                ->count();
        }

        return Inertia::render('GoCheck/Management/Dashboard', [
            'teamsMigrationPending' => $teamsMigrationPending,
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
                'team_count' => $teamCount,
                'upcoming_schedules' => $upcomingSchedules,
            ],
        ]);
    }

    public function teamIndex(Request $request)
    {
        $search = $request->input('search', '');

        try {
            $teams = $this->teamService->teamsQuery($search)->get();
            $serializedTeams = $this->teamService->serializeTeams($teams);

            $schedules = GoCheckSchedule::query()
                ->with(['team:id,inspector_area', 'creator:id,name'])
                ->where('status', 'scheduled')
                ->where('scheduled_date', '>=', now()->toDateString())
                ->orderBy('scheduled_date')
                ->limit(50)
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'team_id' => $s->team_id,
                    'inspector_area' => $s->team?->inspector_area,
                    'scheduled_date' => $s->scheduled_date->format('d/m/Y'),
                    'scheduled_date_raw' => $s->scheduled_date->toDateString(),
                    'target_area' => $s->target_area,
                    'bagian' => $s->bagian,
                    'notes' => $s->notes,
                    'creator_name' => $s->creator?->name,
                ]);

            $allUsers = User::orderBy('name')->get(['id', 'name', 'npp', 'role', 'bagian']);
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('go_check.management.dashboard')
                ->with('error', 'Tabel tim 5R belum tersedia. Jalankan migrasi database (php artisan migrate) di server.');
        }

        return Inertia::render('GoCheck/Management/Team', [
            'teams' => $serializedTeams,
            'schedules' => $schedules,
            'allUsers' => $allUsers,
            'bagianOptions' => self::BAGIAN_OPTIONS,
            'filters' => ['search' => $search],
            'roleOptions' => [
                ['value' => 'five_r_team', 'label' => 'Tim 5R (Finder)'],
                ['value' => 'five_r_ketua', 'label' => 'Ketua 5R'],
                ['value' => 'five_r_sekretaris', 'label' => 'Sekretaris 5R'],
                ['value' => 'user', 'label' => 'User biasa'],
            ],
        ]);
    }

    public function storeTeam(Request $request)
    {
        $validated = $request->validate([
            'inspector_area' => 'required|string|max:255',
        ]);

        $team = FiveRTeam::create([
            'inspector_area' => $validated['inspector_area'],
            'sort_order' => (int) (FiveRTeam::max('sort_order') ?? 0) + 1,
        ]);

        return redirect()
            ->route('go_check.management.team')
            ->with('success', 'Tim area inspector berhasil ditambahkan.')
            ->with('new_team_id', $team->id);
    }

    public function updateTeam(Request $request, FiveRTeam $team)
    {
        $validated = $request->validate([
            'inspector_area' => 'required|string|max:255',
        ]);

        $team->update($validated);

        return back()->with('success', 'Data tim diperbarui.');
    }

    public function destroyTeam(FiveRTeam $team)
    {
        $team->delete();

        return back()->with('success', 'Tim dihapus.');
    }

    public function addTeamMember(Request $request, FiveRTeam $team)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'is_leader' => 'boolean',
            'set_role_five_r_team' => 'boolean',
        ]);

        $user = User::findOrFail($validated['user_id']);

        if ($validated['set_role_five_r_team'] ?? true) {
            if (! $user->isAdmin()) {
                $user->update(['role' => 'five_r_team']);
            }
        }

        FiveRTeamMember::firstOrCreate(
            ['team_id' => $team->id, 'user_id' => $user->id],
            [
                'is_leader' => (bool) ($validated['is_leader'] ?? false),
                'sort_order' => $team->members()->count() + 1,
            ]
        );

        $this->teamService->syncLegacyAssignmentsFromTeam($team->fresh(['members.user', 'auditTargets']));

        return back()->with('success', $user->name.' ditambahkan ke tim.');
    }

    public function removeTeamMember(FiveRTeam $team, User $user)
    {
        FiveRTeamMember::where('team_id', $team->id)->where('user_id', $user->id)->delete();

        return back()->with('success', 'Anggota dihapus dari tim.');
    }

    public function syncTeamTargets(Request $request, FiveRTeam $team)
    {
        $validated = $request->validate([
            'targets' => 'required|array',
            'targets.*.target_area' => 'required|string|max:255',
            'targets.*.pic_name' => 'nullable|string|max:255',
            'targets.*.pic_user_id' => 'nullable|exists:users,id',
            'targets.*.bagian' => 'nullable|string|max:255',
        ]);

        FiveRTeamAuditTarget::where('team_id', $team->id)->delete();

        foreach ($validated['targets'] as $i => $target) {
            if (trim($target['target_area']) === '') {
                continue;
            }
            FiveRTeamAuditTarget::create([
                'team_id' => $team->id,
                'target_area' => $target['target_area'],
                'pic_name' => $target['pic_name'] ?? null,
                'pic_user_id' => $target['pic_user_id'] ?? null,
                'bagian' => $target['bagian'] ?? null,
                'sort_order' => $i + 1,
            ]);
        }

        $this->teamService->syncLegacyAssignmentsFromTeam($team->fresh(['members.user', 'auditTargets']));

        return back()->with('success', 'Penugasan area audit disimpan.');
    }

    public function updateTeamTarget(Request $request, FiveRTeam $team, FiveRTeamAuditTarget $target)
    {
        if ((int) $target->team_id !== (int) $team->id) {
            abort(404);
        }

        $validated = $request->validate([
            'target_area' => 'required|string|max:255',
            'pic_name' => 'nullable|string|max:255',
            'pic_user_id' => 'nullable|exists:users,id',
            'bagian' => 'nullable|string|max:255',
        ]);

        $target->update($validated);

        $this->teamService->syncLegacyAssignmentsFromTeam($team->fresh(['members.user', 'auditTargets']));

        return back()->with('success', 'Penugasan area diperbarui.');
    }

    public function destroyTeamTarget(FiveRTeam $team, FiveRTeamAuditTarget $target)
    {
        if ((int) $target->team_id !== (int) $team->id) {
            abort(404);
        }

        $target->delete();

        $this->teamService->syncLegacyAssignmentsFromTeam($team->fresh(['members.user', 'auditTargets']));

        return back()->with('success', 'Penugasan area dihapus.');
    }

    public function storeSchedule(Request $request)
    {
        $validated = $request->validate([
            'team_id' => 'required|exists:five_r_teams,id',
            'audit_target_id' => 'nullable|exists:five_r_team_audit_targets,id',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'target_area' => 'required|string|max:255',
            'bagian' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $schedule = GoCheckSchedule::create([
            'team_id' => $validated['team_id'],
            'audit_target_id' => $validated['audit_target_id'] ?? null,
            'scheduled_date' => $validated['scheduled_date'],
            'target_area' => $validated['target_area'],
            'bagian' => $validated['bagian'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'scheduled',
            'created_by' => Auth::user()->id,
            'notified_at' => now(),
        ]);

        $this->teamService->notifySchedule($schedule, 'created');

        return back()->with('success', 'Jadwal Go Check dibuat. Notifikasi terkirim ke anggota tim.');
    }

    public function cancelSchedule(GoCheckSchedule $schedule)
    {
        $schedule->update(['status' => 'cancelled']);

        return back()->with('success', 'Jadwal dibatalkan.');
    }

    public function updateSchedule(Request $request, GoCheckSchedule $schedule)
    {
        if ($schedule->status !== 'scheduled') {
            return back()->with('error', 'Hanya jadwal aktif yang dapat diedit.');
        }

        $validated = $request->validate([
            'team_id' => 'required|exists:five_r_teams,id',
            'scheduled_date' => 'required|date',
            'target_area' => 'required|string|max:255',
            'bagian' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $schedule->update([
            'team_id' => $validated['team_id'],
            'scheduled_date' => $validated['scheduled_date'],
            'target_area' => $validated['target_area'],
            'bagian' => $validated['bagian'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Jadwal Go Check diperbarui.');
    }

    public function destroySchedule(GoCheckSchedule $schedule)
    {
        $schedule->delete();

        return back()->with('success', 'Jadwal Go Check dihapus.');
    }

    public function exportTeams()
    {
        return $this->teamService->exportTeamsExcel();
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
