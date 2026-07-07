<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoCheckSchedule extends Model
{
    protected $fillable = [
        'team_id',
        'audit_target_id',
        'scheduled_date',
        'target_area',
        'bagian',
        'notes',
        'status',
        'created_by',
        'notified_at',
        'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'notified_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(FiveRTeam::class, 'team_id');
    }

    public function auditTarget(): BelongsTo
    {
        return $this->belongsTo(FiveRTeamAuditTarget::class, 'audit_target_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
