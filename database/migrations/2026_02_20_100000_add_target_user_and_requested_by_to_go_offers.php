<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('go_offers', function (Blueprint $table) {
            $table->foreignId('target_user_id')->nullable()->after('target_bagian')->constrained('users')->nullOnDelete();
            $table->foreignId('requested_by_user_id')->nullable()->after('accepted_by_user_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('go_offers', function (Blueprint $table) {
            $table->dropForeign(['target_user_id']);
            $table->dropForeign(['requested_by_user_id']);
        });
    }
};
