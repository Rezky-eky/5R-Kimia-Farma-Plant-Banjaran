<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoCare extends Model
{
    use HasFactory;

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
    ];

    /**
     * Relasi dengan model User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
