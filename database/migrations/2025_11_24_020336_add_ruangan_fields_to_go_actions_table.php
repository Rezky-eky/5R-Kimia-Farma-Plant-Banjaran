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
        Schema::table('go_actions', function (Blueprint $table) {
            $table->string('nama_ruangan')->nullable()->after('bagian');
            $table->string('kode_ruangan')->nullable()->after('nama_ruangan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('go_actions', function (Blueprint $table) {
            $table->dropColumn(['nama_ruangan', 'kode_ruangan']);
        });
    }
};
