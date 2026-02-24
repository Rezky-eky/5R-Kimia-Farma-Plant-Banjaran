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
        Schema::table('go_cares', function (Blueprint $table) {
            // Tambah kolom metadata agar selaras dengan GoCareController
            if (!Schema::hasColumn('go_cares', 'nama_karyawan')) {
                $table->string('nama_karyawan')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('go_cares', 'npp_karyawan')) {
                $table->string('npp_karyawan')->nullable()->after('nama_karyawan');
            }
            if (!Schema::hasColumn('go_cares', 'bagian')) {
                $table->string('bagian')->nullable()->after('npp_karyawan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('go_cares', function (Blueprint $table) {
            if (Schema::hasColumn('go_cares', 'bagian')) {
                $table->dropColumn('bagian');
            }
            if (Schema::hasColumn('go_cares', 'npp_karyawan')) {
                $table->dropColumn('npp_karyawan');
            }
            if (Schema::hasColumn('go_cares', 'nama_karyawan')) {
                $table->dropColumn('nama_karyawan');
            }
        });
    }
};
