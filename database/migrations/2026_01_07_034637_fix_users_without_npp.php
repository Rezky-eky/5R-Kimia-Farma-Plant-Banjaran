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
        // Update users yang tidak punya NPP
        $usersWithoutNPP = \App\Models\User::whereNull('npp')->orWhere('npp', '')->get();
        
        foreach ($usersWithoutNPP as $user) {
            // Generate unique NPP
            do {
                $npp = date('Ymd') . str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
            } while (\App\Models\User::where('npp', $npp)->where('id', '!=', $user->id)->exists());
            
            $user->npp = $npp;
            $user->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu rollback karena ini adalah data fix
    }
};
