<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FiveRTeam extends Model
{
    protected $fillable = [
        'inspector_area',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(FiveRTeamMember::class, 'team_id')->orderBy('sort_order')->orderBy('id');
    }

    public function auditTargets(): HasMany
    {
        return $this->hasMany(FiveRTeamAuditTarget::class, 'team_id')->orderBy('sort_order')->orderBy('id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(GoCheckSchedule::class, 'team_id');
    }
}
