<?php

use App\Support\PostcodeFormatter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('locations')
            ->whereNotNull('postcode')
            ->where('postcode', '!=', '')
            ->orderBy('id')
            ->chunk(100, function ($locations) {
                foreach ($locations as $location) {
                    $formatted = PostcodeFormatter::format($location->postcode);

                    if ($formatted !== $location->postcode) {
                        DB::table('locations')
                            ->where('id', $location->id)
                            ->update(['postcode' => $formatted]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Postcodes cannot be un-normalised
    }
};
