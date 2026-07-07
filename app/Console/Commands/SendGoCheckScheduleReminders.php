<?php

namespace App\Console\Commands;

use App\Models\GoCheckSchedule;
use App\Services\GoCheckTeamService;
use Illuminate\Console\Command;

class SendGoCheckScheduleReminders extends Command
{
    protected $signature = 'go-check:schedule-reminders';

    protected $description = 'Kirim notifikasi pengingat Go Check untuk jadwal hari ini';

    public function handle(GoCheckTeamService $service): int
    {
        $schedules = GoCheckSchedule::query()
            ->where('status', 'scheduled')
            ->whereDate('scheduled_date', today())
            ->whereNull('reminder_sent_at')
            ->with('team.members')
            ->get();

        foreach ($schedules as $schedule) {
            $service->notifySchedule($schedule, 'reminder');
            $schedule->update(['reminder_sent_at' => now()]);
        }

        $this->info("Pengingat terkirim untuk {$schedules->count()} jadwal.");

        return self::SUCCESS;
    }
}
