<?php

namespace App\Services;

use App\Models\FiveRTeam;
use App\Models\FiveRTeamAuditTarget;
use App\Models\GoCheckSchedule;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoCheckTeamService
{
    public function teamsQuery(?string $search = null)
    {
        $query = FiveRTeam::query()
            ->with([
                'members.user:id,name,npp,role,bagian',
                'auditTargets.picUser:id,name,npp',
            ])
            ->orderBy('sort_order')
            ->orderBy('inspector_area');

        if ($search) {
            $s = '%'.$search.'%';
            $query->where(function ($q) use ($s) {
                $q->where('inspector_area', 'like', $s)
                    ->orWhereHas('members.user', fn ($uq) => $uq->where('name', 'like', $s)->orWhere('npp', 'like', $s))
                    ->orWhereHas('auditTargets', fn ($tq) => $tq->where('target_area', 'like', $s)->orWhere('pic_name', 'like', $s));
            });
        }

        return $query;
    }

    public function serializeTeams(Collection $teams): array
    {
        return $teams->map(function (FiveRTeam $team) {
            return [
                'id' => $team->id,
                'inspector_area' => $team->inspector_area,
                'is_active' => $team->is_active,
                'members' => $team->members->map(fn ($m) => [
                    'id' => $m->id,
                    'user_id' => $m->user_id,
                    'name' => $m->user?->name,
                    'npp' => $m->user?->npp,
                    'bagian' => $m->user?->bagian,
                    'role' => $m->user?->role,
                    'is_leader' => $m->is_leader,
                ])->values()->all(),
                'audit_targets' => $team->auditTargets->map(fn ($t) => [
                    'id' => $t->id,
                    'target_area' => $t->target_area,
                    'pic_name' => $t->pic_name,
                    'pic_user_id' => $t->pic_user_id,
                    'pic_user_name' => $t->picUser?->name,
                    'bagian' => $t->bagian,
                ])->values()->all(),
            ];
        })->values()->all();
    }

    public function syncLegacyAssignmentsFromTeam(FiveRTeam $team): void
    {
        $bagianValues = $team->auditTargets
            ->flatMap(fn ($t) => array_filter([$t->bagian, $t->target_area]))
            ->unique()
            ->values();

        foreach ($team->members as $member) {
            if (! $member->user?->canActAsGoCheckFinder()) {
                continue;
            }
            foreach ($bagianValues as $bagian) {
                \App\Models\FiveRBagianAssignment::updateOrCreate(
                    ['user_id' => $member->user_id, 'bagian' => $bagian],
                    ['assigned_by' => auth()->user()->id],
                );
            }
        }
    }

    public function notifySchedule(GoCheckSchedule $schedule, string $type = 'created'): void
    {
        $schedule->load(['team.members.user']);
        $dateLabel = $schedule->scheduled_date->format('d/m/Y');

        foreach ($schedule->team->members as $member) {
            if (! $member->user) {
                continue;
            }

            $title = $type === 'reminder'
                ? 'Go Check — Jadwal hari ini'
                : 'Go Check — Jadwal audit baru';

            $message = $type === 'reminder'
                ? "Hari ini ({$dateLabel}) tim Anda wajib melakukan Go Check di **{$schedule->target_area}** (area asal: {$schedule->team->inspector_area})."
                : "Anda dijadwalkan Go Check pada {$dateLabel} untuk area **{$schedule->target_area}** (tim: {$schedule->team->inspector_area}).";

            $payload = [
                'user_id' => $member->user_id,
                'type' => 'go_check_schedule',
                'title' => $title,
                'message' => str_replace('**', '', $message),
            ];

            if (Schema::hasColumn('notifications', 'go_check_schedule_id')) {
                $payload['go_check_schedule_id'] = $schedule->id;
            }

            Notification::create($payload);
        }
    }

    public function exportTeamsExcel(): StreamedResponse
    {
        $teams = $this->teamsQuery()->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tim 5R Go Check');

        $headers = ['No', 'Area Inspector (Tim 5R)', 'No Anggota', 'Nama Anggota Tim', 'NPP', 'Area Pengecekan', 'PIC Area Pengecekan', 'Bagian (Solver)'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        $row = 2;
        $no = 1;
        foreach ($teams as $team) {
            $members = $team->members;
            $targets = $team->auditTargets;
            $maxRows = max($members->count(), $targets->count(), 1);

            for ($i = 0; $i < $maxRows; $i++) {
                $member = $members[$i] ?? null;
                $target = $targets[$i] ?? null;

                $sheet->setCellValueByColumnAndRow(1, $row, $i === 0 ? $no : '');
                $sheet->setCellValueByColumnAndRow(2, $row, $i === 0 ? $team->inspector_area : '');
                $sheet->setCellValueByColumnAndRow(3, $row, $member ? ($i + 1) : '');
                $sheet->setCellValueByColumnAndRow(4, $row, $member?->user?->name ?? '');
                $sheet->setCellValueByColumnAndRow(5, $row, $member?->user?->npp ?? '');
                $sheet->setCellValueByColumnAndRow(6, $row, $target?->target_area ?? '');
                $sheet->setCellValueByColumnAndRow(7, $row, $target?->pic_name ?? $target?->picUser?->name ?? '');
                $sheet->setCellValueByColumnAndRow(8, $row, $target?->bagian ?? '');
                $row++;
            }
            $no++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'laporan-tim-5r-go-check-'.date('Y-m-d').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Normalisasi nama area untuk pencocokan toleran (misal "Area Security, Parkir dan Smoking area" vs "Area Security, Parkir, Smoking area").
     */
    public function normalizeAreaName(?string $name): string
    {
        if (empty($name)) {
            return '';
        }
        $s = mb_strtolower(trim($name));
        $s = str_replace([' dan ', ' & ', '+', '/'], ' ', $s);
        $s = preg_replace('/\barea\b/i', '', $s);
        $s = preg_replace('/[^a-z0-9]/', '', $s);
        return trim($s);
    }

    /**
     * Normalisasi nama orang (menghapus titel honorifik seperti Bu, Pak, dsb).
     */
    public function normalizePersonName(?string $name): string
    {
        if (empty($name)) {
            return '';
        }
        $s = mb_strtolower(trim($name));
        $s = preg_replace('/^(bu|ibu|pak|bapak|mas|mbak|sdr|sdri)\.?\s+/i', '', $s);
        $s = preg_replace('/,\s*(s\.[a-z]+|m\.[a-z]+|drg?|apt\.?|a\.md\.?).*$/i', '', $s);
        $s = preg_replace('/\b(dr|drg|apt)\.?\s+/i', '', $s);
        return trim($s);
    }

    /**
     * Resolusi ketua tim / solver untuk suatu target audit (target area atau bagian).
     */
    public function resolveSolverForTarget(
        FiveRTeamAuditTarget|string $target,
        ?string $picName = null,
        ?int $picUserId = null,
        ?string $bagian = null
    ): ?User {
        $targetModel = null;
        if ($target instanceof FiveRTeamAuditTarget) {
            $targetModel = $target;
            $targetArea = $target->target_area;
            $picName = $target->pic_name ?: $picName;
            $picUserId = $target->pic_user_id ?: $picUserId;
            $bagian = $target->bagian ?: $bagian;
        } else {
            $targetArea = $target;
        }

        // 1. Jika sudah ada pic_user_id yang valid
        if ($picUserId) {
            $user = User::find($picUserId);
            if ($user) {
                return $user;
            }
        }

        $allTeams = FiveRTeam::with(['members.user'])->get();

        // 2. Cari team yang inspector_area cocok dengan target_area
        $normalizedTarget = $this->normalizeAreaName($targetArea);
        $matchedTeam = null;

        if (! empty($normalizedTarget)) {
            $matchedTeam = $allTeams->first(function ($t) use ($normalizedTarget, $targetArea) {
                if (strcasecmp(trim($t->inspector_area), trim($targetArea)) === 0) {
                    return true;
                }
                return $this->normalizeAreaName($t->inspector_area) === $normalizedTarget;
            });

            if (! $matchedTeam && strlen($normalizedTarget) >= 4) {
                $matchedTeam = $allTeams->first(function ($t) use ($normalizedTarget) {
                    $normTeam = $this->normalizeAreaName($t->inspector_area);
                    return ! empty($normTeam) && (str_contains($normTeam, $normalizedTarget) || str_contains($normalizedTarget, $normTeam));
                });
            }
        }

        if ($matchedTeam) {
            $leaderMember = $matchedTeam->members->firstWhere('is_leader', true)
                ?? $matchedTeam->members->first();

            if ($leaderMember?->user) {
                $resolved = $leaderMember->user;
                if ($targetModel && ! $targetModel->pic_user_id) {
                    $targetModel->update(['pic_user_id' => $resolved->id]);
                    if (empty($targetModel->pic_name)) {
                        $targetModel->update(['pic_name' => $resolved->name]);
                    }
                }
                return $resolved;
            }
        }

        // 3. Cocokkan berdasarkan nama PIC jika diisi
        if (! empty($picName)) {
            $cleanPic = $this->normalizePersonName($picName);

            if (! empty($cleanPic)) {
                $allLeaders = \App\Models\FiveRTeamMember::where('is_leader', true)
                    ->with('user')
                    ->get()
                    ->pluck('user')
                    ->filter();

                $matchedLeader = $allLeaders->first(function ($u) use ($cleanPic) {
                    $cleanUser = $this->normalizePersonName($u->name);
                    if (strcasecmp($cleanUser, $cleanPic) === 0) {
                        return true;
                    }
                    if (str_contains($cleanUser, $cleanPic) || str_contains($cleanPic, $cleanUser)) {
                        return true;
                    }
                    $picWords = array_filter(explode(' ', $cleanPic), fn ($w) => strlen($w) >= 3);
                    $userWords = array_filter(explode(' ', $cleanUser), fn ($w) => strlen($w) >= 3);
                    return ! empty(array_intersect($picWords, $userWords));
                });

                if ($matchedLeader) {
                    if ($targetModel && ! $targetModel->pic_user_id) {
                        $targetModel->update(['pic_user_id' => $matchedLeader->id]);
                    }
                    return $matchedLeader;
                }

                $matchedUser = User::where('name', 'like', '%'.$cleanPic.'%')->first();
                if ($matchedUser) {
                    if ($targetModel && ! $targetModel->pic_user_id) {
                        $targetModel->update(['pic_user_id' => $matchedUser->id]);
                    }
                    return $matchedUser;
                }
            }
        }

        // 4. Jika ada bagian, cocokkan dengan inspector_area atau user bagian
        if (! empty($bagian)) {
            $normBagian = $this->normalizeAreaName($bagian);
            $matchedBagianTeam = $allTeams->first(function ($t) use ($normBagian, $bagian) {
                if (strcasecmp(trim($t->inspector_area), trim($bagian)) === 0) {
                    return true;
                }
                return ! empty($normBagian) && $this->normalizeAreaName($t->inspector_area) === $normBagian;
            });

            if ($matchedBagianTeam) {
                $leaderMember = $matchedBagianTeam->members->firstWhere('is_leader', true)
                    ?? $matchedBagianTeam->members->first();
                if ($leaderMember?->user) {
                    if ($targetModel && ! $targetModel->pic_user_id) {
                        $targetModel->update(['pic_user_id' => $leaderMember->user->id]);
                    }
                    return $leaderMember->user;
                }
            }
        }

        return null;
    }
}

