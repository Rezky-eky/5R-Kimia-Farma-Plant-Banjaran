<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class GoCare extends Model
{
    use HasFactory;

    public const POINTS_PER_APPROVAL = 10;

    /**
     * Nama tabel yang digunakan oleh model.
     *
     * @var string
     */
    protected $table = 'go_cares';

    /**
     * Properti yang boleh diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'nama_karyawan',
        'npp_karyawan',
        'bagian',
        'bagian_temuan',
        'area_temuan',
        'penjelasan_temuan',
        'photo_before',
        'penjelasan_capa',
        'photo_after',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'reject_comment',
    ];

    protected $casts = [
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

        return ['approval_status' => 'PENDING'];
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

    /**
     * Relasi dengan model User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
