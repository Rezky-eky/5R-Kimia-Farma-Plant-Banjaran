<?php

namespace App\Http\Controllers;

use App\Models\FiveRTeamAuditTarget;
use App\Models\FiveRTeamMember;
use App\Models\GoCheck;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $allLeaders = $this->allInspectorTeamLeaders($user);

        return Inertia::render('GoCheck/Create', [
            'assignedBagian' => $assignedBagian,
            'solverLeaders' => $allLeaders,
        ]);
    }

    /**
     * @return list<array{id: int, name: string, npp: string, team_name: string|null}>
     */
    private function allInspectorTeamLeaders(User $finder): array
    {
        return FiveRTeamMember::query()
            ->where('is_leader', true)
            ->where('user_id', '!=', $finder->id)
            ->with(['user:id,name,npp', 'team:id,inspector_area'])
            ->orderBy('team_id')
            ->get()
            ->map(fn ($member) => [
                'id' => $member->user->id,
                'name' => $member->user->name,
                'npp' => $member->user->npp,
                'team_name' => $member->team?->inspector_area,
            ])
            ->unique('id')
            ->values()
            ->all();
    }

    private function solverAllowed(User $finder, int $solverId): bool
    {
        return collect($this->allInspectorTeamLeaders($finder))
            ->contains(fn ($row) => (int) $row['id'] === $solverId);
    }

    /**
     * @return array<string, array{solver_bagian: string|null, default_solver_id: int|null}>
     */
    private function assignmentMetaMap(User $user): array
    {
        $meta = [];

        FiveRTeamMember::query()
            ->where('user_id', $user->id)
            ->with('team.auditTargets')
            ->get()
            ->each(function ($membership) use (&$meta) {
                foreach ($membership->team?->auditTargets ?? [] as $target) {
                    foreach (array_filter([$target->bagian, $target->target_area]) as $key) {
                        $meta[$key] = [
                            'solver_bagian' => $target->bagian ?: $key,
                            'default_solver_id' => $target->pic_user_id,
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
            'photo_temuan.*' => 'image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        if (! in_array($validated['bagian'], $assignedBagian, true)) {
            return back()->withErrors(['bagian' => 'Bagian tidak termasuk penugasan Anda.'])->withInput();
        }

        $solver = User::findOrFail($validated['solver_user_id']);

        if ((int) $solver->id === (int) $user->id) {
            return back()->withErrors(['solver_user_id' => 'Finder tidak dapat menjadi Solver.'])->withInput();
        }

        if (! $this->solverAllowed($user, (int) $solver->id)) {
            return back()->withErrors(['solver_user_id' => 'Solver harus ketua tim inspector yang terdaftar.'])->withInput();
        }

        $meta = $this->assignmentMetaMap($user);
        $storeBagian = $meta[$validated['bagian']]['solver_bagian'] ?? $validated['bagian'];

        $photoPaths = [];
        if ($request->hasFile('photo_temuan')) {
            foreach ($request->file('photo_temuan') as $file) {
                $photoPaths[] = $file->store('go_checks', 'public');
            }
        }

        $goCheck = GoCheck::create([
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

        if ($goCheck->finder_user_id === $user->id) {
            return back()->withErrors(['error' => 'Finder tidak dapat menjadi Solver pada temuan yang sama.']);
        }

        $validated = $request->validate([
            'keterangan_perbaikan' => 'required|string',
            'foto_perbaikan' => 'nullable|array|max:5',
            'foto_perbaikan.*' => 'image|mimes:jpeg,png,jpg,gif|max:10240',
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
