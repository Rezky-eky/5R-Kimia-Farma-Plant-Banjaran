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
            $table->decimal('latitude', 10, 8)->nullable()->after('penjelasan_aksi');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->json('list_barang_ringkas')->nullable()->after('longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('go_actions', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'list_barang_ringkas']);
        });
    }
};
