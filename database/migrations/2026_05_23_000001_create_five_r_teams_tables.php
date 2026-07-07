<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('five_r_teams', function (Blueprint $table) {
            $table->id();
            $table->string('inspector_area');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('five_r_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('five_r_teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_leader')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
        });

        Schema::create('five_r_team_audit_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('five_r_teams')->cascadeOnDelete();
            $table->string('target_area');
            $table->string('pic_name')->nullable();
            $table->foreignId('pic_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('bagian')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('go_check_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('five_r_teams')->cascadeOnDelete();
            $table->foreignId('audit_target_id')->nullable()->constrained('five_r_team_audit_targets')->nullOnDelete();
            $table->date('scheduled_date');
            $table->string('target_area');
            $table->string('bagian')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('scheduled');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('go_check_schedules');
        Schema::dropIfExists('five_r_team_audit_targets');
        Schema::dropIfExists('five_r_team_members');
        Schema::dropIfExists('five_r_teams');
    }
};
