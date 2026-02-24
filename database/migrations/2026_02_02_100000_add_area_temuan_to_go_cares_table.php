<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('go_cares', function (Blueprint $table) {
            if (!Schema::hasColumn('go_cares', 'area_temuan')) {
                $table->string('area_temuan')->nullable()->after('bagian_temuan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('go_cares', function (Blueprint $table) {
            if (Schema::hasColumn('go_cares', 'area_temuan')) {
                $table->dropColumn('area_temuan');
            }
        });
    }
};
