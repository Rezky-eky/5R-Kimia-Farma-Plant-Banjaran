<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class GoBoost extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang digunakan oleh model.
     *
     * @var string
     */
    protected $table = 'go_boosts';

    /**
     * Properti yang boleh diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'mentioned_user_id',
        'nama_karyawan',
        'npp_karyawan',
        'bagian',
        'area_temuan',
        'ruangan_temuan',
        'penjelasan_temuan',
        'pic_terkait',
        'photo_temuan',
        'status',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'reject_comment',
        'keterangan_perbaikan',
        'foto_perbaikan',
        'status_perbaikan',
        'tanggal_perbaikan',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_perbaikan' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Apakah tabel go_boosts sudah punya kolom workflow approval (setelah migrate).
     */
    public static function hasApprovalWorkflow(): bool
    {
        static $cached = null;

        if ($cached === null) {
            $cached = Schema::hasColumn((new static)->getTable(), 'approval_status');
        }

        return $cached;
    }

    /**
     * Atribut reset approval saat solver submit perbaikan.
     */
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

    /**
     * Atribut saat admin approve perbaikan.
     */
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

    /**
     * Atribut saat admin reject perbaikan.
     */
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
     * Relasi dengan model User (pembuat boost).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi dengan model User (user yang di-mention).
     */
    public function mentionedUser()
    {
        return $this->belongsTo(User::class, 'mentioned_user_id');
    }

    /**
     * Relasi dengan model Notification.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
