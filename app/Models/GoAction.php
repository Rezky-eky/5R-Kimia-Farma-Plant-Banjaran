<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoAction extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang digunakan oleh model.
     *
     * @var string
     */
    protected $table = 'go_actions';

    /**
     * Properti yang boleh diisi secara massal (mass assignable).
     * Jika ini tidak ada atau tidak lengkap, data tidak akan tersimpan.
     */
    protected $fillable = [
        'user_id',
        'npp_karyawan',
        'nama_karyawan',
        'metode',
        'bagian',
        'nama_ruangan',
        'kode_ruangan',
        'penjelasan_aksi',
        'foto_kegiatan_path',
        'latitude',
        'longitude',
        'list_barang_ringkas',
    ];

    /**
     * Cast attributes to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'list_barang_ringkas' => 'array',
    ];

    /**
     * Relasi dengan model User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi dengan model Audit.
     */
    public function audit()
    {
        return $this->hasOne(Audit::class);
    }
}
