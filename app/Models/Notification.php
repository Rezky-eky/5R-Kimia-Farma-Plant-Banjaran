<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'go_boost_id',
        'go_care_id',
        'go_check_id',
        'type',
        'title',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Relasi dengan model User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi dengan model GoBoost.
     */
    public function goBoost(): BelongsTo
    {
        return $this->belongsTo(GoBoost::class);
    }

    public function goCare(): BelongsTo
    {
        return $this->belongsTo(GoCare::class);
    }

    public function goCheck(): BelongsTo
    {
        return $this->belongsTo(GoCheck::class);
    }
}
