<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('go_cares', function (Blueprint $table) {
            $table->string('approval_status')->default('PENDING')->after('photo_after'); // PENDING|APPROVED|REJECTED
            $table->foreignId('approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->foreignId('rejected_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->text('reject_comment')->nullable()->after('rejected_at');
        });
    }

    public function down(): void
    {
        Schema::table('go_cares', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn([
                'approval_status',
                'approved_at',
                'rejected_at',
                'reject_comment',
            ]);
        });
    }
};

