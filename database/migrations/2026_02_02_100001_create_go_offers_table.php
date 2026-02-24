<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('go_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('go_action_id')->constrained('go_actions')->cascadeOnDelete();
            $table->unsignedSmallInteger('dbr_index')->default(0);
            $table->json('dbr_snapshot')->nullable();
            $table->foreignId('offered_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('offered_by_bagian')->nullable();
            $table->string('target_bagian');
            $table->string('status')->default('pending'); // pending, accepted, rejected
            $table->foreignId('accepted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('go_offers');
    }
};
