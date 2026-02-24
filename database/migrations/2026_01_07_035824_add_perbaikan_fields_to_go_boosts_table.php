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
            if (!Schema::hasColumn('go_boosts', 'keterangan_perbaikan')) {
                $table->text('keterangan_perbaikan')->nullable()->after('photo_temuan');
            }
            if (!Schema::hasColumn('go_boosts', 'foto_perbaikan')) {
                $table->text('foto_perbaikan')->nullable()->after('keterangan_perbaikan');
            }
            if (!Schema::hasColumn('go_boosts', 'status_perbaikan')) {
                $table->enum('status_perbaikan', ['pending', 'dalam_perbaikan', 'selesai'])->default('pending')->after('foto_perbaikan');
            }
            if (!Schema::hasColumn('go_boosts', 'tanggal_perbaikan')) {
                $table->timestamp('tanggal_perbaikan')->nullable()->after('status_perbaikan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('go_boosts', function (Blueprint $table) {
            if (Schema::hasColumn('go_boosts', 'tanggal_perbaikan')) {
                $table->dropColumn('tanggal_perbaikan');
            }
            if (Schema::hasColumn('go_boosts', 'status_perbaikan')) {
                $table->dropColumn('status_perbaikan');
            }
            if (Schema::hasColumn('go_boosts', 'foto_perbaikan')) {
                $table->dropColumn('foto_perbaikan');
            }
            if (Schema::hasColumn('go_boosts', 'keterangan_perbaikan')) {
                $table->dropColumn('keterangan_perbaikan');
            }
        });
    }
};
