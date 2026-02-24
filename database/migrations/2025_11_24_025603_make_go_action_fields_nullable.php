<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; 

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        // 1. **LANGKAH PENTING: Perbaiki data JSON yang tidak valid.**
        // Kita gunakan fungsi JSON_VALID() untuk mengidentifikasi dan mengubah
        // SEMUA nilai di kolom 'foto_kegiatan_path' yang bukan JSON valid (termasuk string kosong,
        // atau string aneh lainnya) menjadi NULL. Ini mengatasi error 3140.
        // Kita juga pastikan kolom 'penjelasan_aksi' tidak mengandung string kosong ('') jika ia harus menjadi NULL.
        
        DB::statement("UPDATE go_actions SET penjelasan_aksi = NULL WHERE penjelasan_aksi = ''");
        
        // Query utama untuk memperbaiki JSON:
        // Cek data yang BUKAN NULL dan BUKAN JSON yang valid
        DB::statement("UPDATE go_actions SET foto_kegiatan_path = NULL WHERE foto_kegiatan_path IS NOT NULL AND JSON_VALID(foto_kegiatan_path) = 0");

        // 2. Lanjutkan dengan mengubah skema tabel
        Schema::table('go_actions', function (Blueprint $table) {
            
            // Jadikan 'penjelasan_aksi' opsional
            $table->text('penjelasan_aksi')->nullable()->change();

            // Jadikan 'foto_kegiatan_path' opsional
            // Setelah langkah pembersihan di atas, ini seharusnya berhasil 100%.
            $table->json('foto_kegiatan_path')->nullable()->change();
        });
    }

    /**
     * Balikkan (rollback) migrasi.
     */
    public function down(): void
    {
        Schema::table('go_actions', function (Blueprint $table) {
            
            // Karena 'penjelasan_aksi' awalnya mungkin NOT NULL, kita kembalikan.
            $table->text('penjelasan_aksi')->nullable(false)->change();
            
            // Kembalikan 'foto_kegiatan_path' ke NOT NULL.
            $table->json('foto_kegiatan_path')->nullable(false)->change();
        });
    }
};