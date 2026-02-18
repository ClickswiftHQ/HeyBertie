<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->integer('sort_order')->default(0);
        });

        DB::table('subscription_statuses')->insert([
            ['name' => 'Trial', 'slug' => 'trial', 'sort_order' => 1],
            ['name' => 'Active', 'slug' => 'active', 'sort_order' => 2],
            ['name' => 'Past Due', 'slug' => 'past_due', 'sort_order' => 3],
            ['name' => 'Cancelled', 'slug' => 'cancelled', 'sort_order' => 4],
            ['name' => 'Suspended', 'slug' => 'suspended', 'sort_order' => 5],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_statuses');
    }
};
