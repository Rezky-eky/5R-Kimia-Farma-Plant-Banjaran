<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('go_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('go_action_id')->constrained('go_actions')->cascadeOnDelete();
            $table->unsignedSmallInteger('dbr_index')->default(0);
            $table->json('dbr_snapshot')->nullable();
            $table->foreignId('seller_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('seller_bagian')->nullable();
            $table->foreignId('buyer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('buyer_bagian')->nullable();
            $table->decimal('agreed_price', 12, 2)->nullable();
            $table->string('status')->default('pending'); // pending, completed, cancelled
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('go_sales');
    }
};
