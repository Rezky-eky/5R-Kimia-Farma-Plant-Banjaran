<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class GoCheck extends Model
{
    protected $fillable = [
        'finder_user_id',
        'solver_user_id',
        'bagian',
        'area_temuan',
        'ruangan_temuan',
        'penjelasan_temuan',
        'pic_terkait',
        'photo_temuan',
        'status',
        'keterangan_perbaikan',
        'foto_perbaikan',
        'status_perbaikan',
        'tanggal_perbaikan',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'reject_comment',
    ];

    protected $casts = [
        'tanggal_perbaikan' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public static function hasApprovalWorkflow(): bool
    {
        static $cached = null;

        if ($cached === null) {
            $cached = Schema::hasColumn((new static)->getTable(), 'approval_status');
        }

        return $cached;
    }

    public static function pendingApprovalAttributes(): array
    {
        if (! static::hasApprovalWorkflow()) {
            return [];
        }

        return [
            'approval_status' => 'PENDING',
            'approved_by' => null,
            'approved_at' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'reject_comment' => null,
        ];
    }

    public static function approvedAttributes(int|string $approvedBy): array
    {
        if (! static::hasApprovalWorkflow()) {
            return [];
        }

        return [
            'approval_status' => 'APPROVED',
            'approved_by' => (int) $approvedBy,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'reject_comment' => null,
        ];
    }

    public static function rejectedAttributes(int|string $rejectedBy, string $comment): array
    {
        if (! static::hasApprovalWorkflow()) {
            return [];
        }

        return [
            'approval_status' => 'REJECTED',
            'rejected_by' => (int) $rejectedBy,
            'rejected_at' => now(),
            'reject_comment' => $comment,
            'approved_by' => null,
            'approved_at' => null,
        ];
    }

    public function finder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finder_user_id');
    }

    public function solver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solver_user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
