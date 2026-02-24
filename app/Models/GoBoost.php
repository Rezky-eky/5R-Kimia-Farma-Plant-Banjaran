<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

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
