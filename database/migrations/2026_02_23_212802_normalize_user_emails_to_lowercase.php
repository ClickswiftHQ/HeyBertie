<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereRaw('email != LOWER(email)')
            ->update(['email' => DB::raw('LOWER(email)')]);

        DB::table('customers')
            ->whereRaw('email != LOWER(email)')
            ->update(['email' => DB::raw('LOWER(email)')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible — original casing is lost.
    }
};
