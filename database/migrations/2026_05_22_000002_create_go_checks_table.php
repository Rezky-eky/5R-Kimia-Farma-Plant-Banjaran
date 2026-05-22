<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('go_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finder_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('solver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('bagian');
            $table->string('area_temuan');
            $table->string('ruangan_temuan');
            $table->text('penjelasan_temuan');
            $table->string('pic_terkait')->nullable();
            $table->text('photo_temuan')->nullable();
            $table->string('status')->default('OPEN');
            $table->text('keterangan_perbaikan')->nullable();
            $table->text('foto_perbaikan')->nullable();
            $table->string('status_perbaikan')->default('pending');
            $table->timestamp('tanggal_perbaikan')->nullable();
            $table->string('approval_status')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('reject_comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('go_checks');
    }
};
