<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiveRTeamMember extends Model
{
    protected $fillable = [
        'team_id',
        'user_id',
        'is_leader',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_leader' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(FiveRTeam::class, 'team_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
