<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    use HasFactory;

    protected $fillable = [
        'go_action_id',
        'score',
        'notes',
        'auditor_id',
    ];

    /**
     * Relasi dengan model GoAction.
     */
    public function goAction()
    {
        return $this->belongsTo(GoAction::class);
    }

    /**
     * Relasi dengan model User (auditor).
     */
    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }
}
