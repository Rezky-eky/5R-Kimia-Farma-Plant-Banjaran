<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('notifications', 'go_check_id')) {
                $table->foreignId('go_check_id')->nullable()->after('go_care_id')->constrained('go_checks')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'go_check_id')) {
                $table->dropConstrainedForeignId('go_check_id');
            }
        });
    }
};
