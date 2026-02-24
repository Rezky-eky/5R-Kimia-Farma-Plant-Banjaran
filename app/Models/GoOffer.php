<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoOffer extends Model
{
    protected $fillable = [
        'go_action_id',
        'dbr_index',
        'dbr_snapshot',
        'offered_by_user_id',
        'offered_by_bagian',
        'target_bagian',
        'target_user_id',
        'status',
        'accepted_by_user_id',
        'requested_by_user_id',
        'accepted_at',
    ];

    protected $casts = [
        'dbr_snapshot' => 'array',
        'accepted_at' => 'datetime',
    ];

    public function goAction(): BelongsTo
    {
        return $this->belongsTo(GoAction::class);
    }

    public function offeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'offered_by_user_id');
    }

    public function acceptedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }
}
