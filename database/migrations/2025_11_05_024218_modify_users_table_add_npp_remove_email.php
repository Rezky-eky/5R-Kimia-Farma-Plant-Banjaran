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
        Schema::table('users', function (Blueprint $table) {
            // Tambah kolom npp (string, unique, NOT NULL)
            $table->string('npp')->unique()->nullable(false)->after('name');
        });
        
        Schema::table('users', function (Blueprint $table) {
            // Hapus kolom email dan email_verified_at
            $table->dropColumn(['email', 'email_verified_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kembalikan kolom email
            $table->string('email')->unique()->after('name');
            
            // Kembalikan kolom email_verified_at
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });
        
        Schema::table('users', function (Blueprint $table) {
            // Hapus kolom npp
            $table->dropColumn('npp');
        });
    }
};
