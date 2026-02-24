<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('go_boosts', function (Blueprint $table) {
            // Cek apakah kolom sudah ada sebelum menambahkan
            if (!Schema::hasColumn('go_boosts', 'nama_karyawan')) {
                $table->string('nama_karyawan')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('go_boosts', 'npp_karyawan')) {
                $table->string('npp_karyawan')->nullable()->after('nama_karyawan');
            }
            if (!Schema::hasColumn('go_boosts', 'bagian')) {
                $table->string('bagian')->nullable()->after('npp_karyawan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('go_boosts', function (Blueprint $table) {
            if (Schema::hasColumn('go_boosts', 'bagian')) {
                $table->dropColumn('bagian');
            }
            if (Schema::hasColumn('go_boosts', 'npp_karyawan')) {
                $table->dropColumn('npp_karyawan');
            }
            if (Schema::hasColumn('go_boosts', 'nama_karyawan')) {
                $table->dropColumn('nama_karyawan');
            }
        });
    }
};
