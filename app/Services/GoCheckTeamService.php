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
}
