<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiveRTeamAuditTarget extends Model
{
    protected $fillable = [
        'team_id',
        'target_area',
        'pic_name',
        'pic_user_id',
        'bagian',
        'sort_order',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(FiveRTeam::class, 'team_id');
    }

    public function picUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }
}
