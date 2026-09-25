<?php

namespace App\Http\Controllers;

use App\Models\FiveRTeamAuditTarget;
use App\Models\FiveRTeamMember;
use App\Models\GoCheck;
use App\Models\Notification;
use App\Models\User;
use App\Services\GoCheckTeamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class GoCheckController extends Controller
{
    public function create()
    {
        $user = Auth::user();
        $assignedBagian = $user->assignedBagianList();

        if (empty($assignedBagian)) {
            return redirect()->route('dashboard')->with(
                'error',
                'Anda belum memiliki penugasan bagian dari Ketua/Sekretaris. Hubungi manajemen 5R.'
            );
        }

        // Ambil semua audit targets milik tim di mana user ini menjadi anggota,
        // beserta relasi picUser agar kita bisa langsung pakai data PIC-nya.
        $userTargets = FiveRTeamMember::where('user_id', $user->id)
            ->with('team.auditTargets.picUser:id,name,npp')
            ->get()
            ->flatMap(fn ($m) => $m->team?->auditTargets ?? collect())
            ->values();

        // Kumpulkan PIC unik berdasarkan pic_user_id yang sudah terdaftar di database.
        // Jika finder sendiri adalah PIC di suatu area, tetap ikutkan (tidak difilter).
        $picUsersById    = [];
        $assignmentSolverMap = [];

        foreach ($assignedBagian as $areaOrBagian) {
            // Cari audit target yang cocok dengan area/bagian ini
            $matchedTarget = $userTargets->first(function ($t) use ($areaOrBagian) {
                return $t->target_area === $areaOrBagian || $t->bagian === $areaOrBagian;
            });

            if (! $matchedTarget) {
                continue;
            }

            // Ambil PIC langsung dari pic_user_id — ini adalah nama dari kolom PIC Area Pengecekan di Excel.
            // Tidak menggunakan resolveSolverForTarget() agar tidak jatuh ke ketua tim.
            $picUser = $matchedTarget->picUser;

            // Jika pic_user_id belum terisi tapi pic_name ada, coba cari user berdasarkan nama
            if (! $picUser && $matchedTarget->pic_name) {
                $picUser = User::where('name', 'like', '%'.trim($matchedTarget->pic_name).'%')->first();
            }

            if (! $picUser) {
                continue;
            }

            $teamName = $matchedTarget->target_area;
            $picName  = $matchedTarget->pic_name ?: $picUser->name;

            // Tambahkan ke daftar PIC (termasuk jika PIC adalah finder sendiri)
            if (! isset($picUsersById[$picUser->id])) {
                $picUsersById[$picUser->id] = [
                    'id'        => $picUser->id,
                    'name'      => $picUser->name,
                    'npp'       => $picUser->npp,
                    'team_name' => $teamName,
                ];
            }

            $assignmentSolverMap[$areaOrBagian] = [
                'solver_user_id' => $picUser->id,
                'solver_name'    => $picUser->name,
                'solver_npp'     => $picUser->npp,
                'team_name'      => $teamName,
                'pic_name'       => $picName,
            ];
        }

        $allPicUsers = array_values($picUsersById);

        $defaultBagian   = $assignedBagian[0] ?? '';
        $defaultSolverId = $assignmentSolverMap[$defaultBagian]['solver_user_id']
            ?? ($allPicUsers[0]['id'] ?? '');

        return Inertia::render('GoCheck/Create', [
            'assignedBagian'      => $assignedBagian,
            'solverLeaders'       => $allPicUsers,
            'assignmentSolverMap' => $assignmentSolverMap,
            'defaultBagian'       => $defaultBagian,
            'defaultSolverId'     => $defaultSolverId,
        ]);
    }

    /**
     * Validasi apakah solverId diizinkan untuk finder ini.
     * PIC boleh sama dengan finder (kasus finder sekaligus PIC area).
     */
    private function picAreaUserIds(User $finder): array
    {
        $targets = FiveRTeamMember::where('user_id', $finder->id)
            ->with('team.auditTargets.picUser:id,name,npp')
            ->get()
            ->flatMap(fn ($m) => $m->team?->auditTargets ?? collect())
            ->values();

        $ids = [];

        foreach ($targets as $target) {
            if ($target->pic_user_id) {
                $ids[] = (int) $target->pic_user_id;
            } elseif ($target->pic_name) {
                // Fallback: cari user berdasarkan pic_name jika pic_user_id belum terisi
                $u = User::where('name', 'like', '%'.trim($target->pic_name).'%')->first();
                if ($u) {
                    $ids[] = (int) $u->id;
                }
            }
        }

        return array_unique($ids);
    }

    private function solverAllowed(User $finder, int $solverId): bool
    {
        // PIC area pengecekan (termasuk jika finder sendiri adalah PIC suatu area)
        if (in_array($solverId, $this->picAreaUserIds($finder), true)) {
            return true;
        }

        return false;
    }

    /**
     * @return array<string, array{solver_bagian: string|null, default_solver_id: int|null}>
     */
    private function assignmentMetaMap(User $user): array
    {
        $meta = [];
        $teamService = app(GoCheckTeamService::class);

        FiveRTeamMember::query()
            ->where('user_id', $user->id)
            ->with('team.auditTargets')
            ->get()
            ->each(function ($membership) use (&$meta, $teamService) {
                foreach ($membership->team?->auditTargets ?? [] as $target) {
                    $resolvedUser = $teamService->resolveSolverForTarget($target);
                    foreach (array_filter([$target->bagian, $target->target_area]) as $key) {
                        $meta[$key] = [
                            'solver_bagian' => $target->bagian ?: $key,
                            'default_solver_id' => $resolvedUser?->id ?? $target->pic_user_id,
                        ];
                    }
                }
            });

        foreach ($user->fiveRBagianAssignments()->pluck('bagian') as $bagian) {
            if ($bagian && ! isset($meta[$bagian])) {
                $meta[$bagian] = [
                    'solver_bagian' => $bagian,
                    'default_solver_id' => null,
                ];
            }
        }

        return $meta;
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $assignedBagian = $user->assignedBagianList();

        $validated = $request->validate([
            'bagian' => 'required|string|max:255',
            'solver_user_id' => 'required|exists:users,id',
            'area_temuan' => 'required|string|max:255',
            'ruangan_temuan' => 'required|string|max:255',
            'penjelasan_temuan' => 'required|string',
            'pic_terkait' => 'nullable|string|max:255',
            'photo_temuan' => 'nullable|array|max:5',
            'photo_temuan.*' => 'file|max:10240',
        ]);

        if (! in_array($validated['bagian'], $assignedBagian, true)) {
            return back()->withErrors(['bagian' => 'Bagian tidak termasuk penugasan Anda.'])->withInput();
        }

        $solver = User::findOrFail($validated['solver_user_id']);

        if (! $this->solverAllowed($user, (int) $solver->id)) {
            return back()->withErrors(['solver_user_id' => 'Solver harus PIC area pengecekan yang terdaftar.'])->withInput();
        }

        $meta = $this->assignmentMetaMap($user);
        $storeBagian = $meta[$validated['bagian']]['solver_bagian'] ?? $validated['bagian'];

        $photoPaths = [];
        if ($request->hasFile('photo_temuan')) {
            foreach ($request->file('photo_temuan') as $file) {
                $photoPaths[] = $file->store('go_checks', 'public');
            }
        }

        $lockName = 'go_checks_id_allocator';
        $locked = (int) DB::selectOne('SELECT GET_LOCK(?, 5) AS acquired', [$lockName])->acquired === 1;
        if (! $locked) {
            throw new \RuntimeException('Tidak dapat mengalokasikan ID Go Check. Silakan coba lagi.');
        }

        try {
            $nextId = ((int) DB::table('go_checks')->max('id')) + 1;
            $goCheck = GoCheck::create([
                'id' => $nextId,
                'finder_user_id' => $user->id,
                'solver_user_id' => $solver->id,
                'bagian' => $storeBagian,
                'area_temuan' => $validated['area_temuan'],
                'ruangan_temuan' => $validated['ruangan_temuan'],
                'penjelasan_temuan' => $validated['penjelasan_temuan'],
                'pic_terkait' => $validated['pic_terkait'] ?? null,
                'photo_temuan' => ! empty($photoPaths) ? json_encode($photoPaths) : null,
                'status' => 'OPEN',
                'status_perbaikan' => 'pending',
            ]);
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
        }

        Notification::create([
            'user_id' => $solver->id,
            'go_check_id' => $goCheck->id,
            'type' => 'go_check_solver_needed',
            'title' => 'Go Check — Perlu tindak lanjut (Solver)',
            'message' => 'Tim 5R menemukan temuan di bagian Anda ('.$storeBagian.'). Silakan input perbaikan sebagai Solver.',
        ]);

        return redirect()->route('dashboard')->with(
            'success',
            'Go Check berhasil dicatat. Menunggu Solver: '.$solver->name.'.'
        );
    }

    public function submitPerbaikan(Request $request, $id)
    {
        $user = Auth::user();
        $goCheck = GoCheck::findOrFail($id);

        if ($goCheck->status_perbaikan === 'selesai') {
            return back()->withErrors(['error' => 'Perbaikan sudah disubmit.']);
        }

        if ($goCheck->solver_user_id && (int) $goCheck->solver_user_id !== (int) $user->id) {
            return back()->withErrors(['error' => 'Hanya Solver yang ditunjuk yang dapat menginput perbaikan.']);
        }

        if (! $goCheck->solver_user_id && ($user->bagian ?? '') !== $goCheck->bagian) {
            return back()->withErrors(['error' => 'Hanya karyawan bagian '.$goCheck->bagian.' yang dapat menjadi Solver.']);
        }

        // Catatan: finder yang juga menjadi PIC (solver) area tersebut diizinkan submit perbaikan.

        $validated = $request->validate([
            'keterangan_perbaikan' => 'required|string',
            'foto_perbaikan' => 'nullable|array|max:5',
            'foto_perbaikan.*' => 'file|max:10240',
        ]);

        $photoPaths = [];
        if ($request->hasFile('foto_perbaikan')) {
            foreach ($request->file('foto_perbaikan') as $file) {
                $photoPaths[] = $file->store('go_checks/perbaikan', 'public');
            }
        }

        $goCheck->update(array_merge([
            'solver_user_id' => $user->id,
            'keterangan_perbaikan' => $validated['keterangan_perbaikan'],
            'foto_perbaikan' => ! empty($photoPaths) ? json_encode($photoPaths) : null,
            'status_perbaikan' => 'selesai',
            'tanggal_perbaikan' => now(),
            'status' => 'CLOSED',
        ], GoCheck::pendingApprovalAttributes()));

        Notification::create([
            'user_id' => $goCheck->finder_user_id,
            'go_check_id' => $goCheck->id,
            'type' => 'go_check_perbaikan',
            'title' => 'Go Check — Perbaikan masuk (menunggu approval)',
            'message' => $user->name.' (Solver) telah menginput perbaikan untuk temuan di '.$goCheck->bagian.'. Menunggu persetujuan manajemen 5R.',
        ]);

        $managers = User::whereIn('role', ['admin', 'five_r_ketua', 'five_r_sekretaris'])->get();
        foreach ($managers as $manager) {
            Notification::create([
                'user_id' => $manager->id,
                'go_check_id' => $goCheck->id,
                'type' => 'go_check_pending_approval',
                'title' => 'Go Check — Siap di-approve',
                'message' => 'Perbaikan Go Check di '.$goCheck->bagian.' sudah lengkap. Silakan approve/reject di Kelola Go Check.',
            ]);
        }

        return back()->with('success', 'Perbaikan Go Check berhasil disubmit. Menunggu approval manajemen 5R.');
    }
}
