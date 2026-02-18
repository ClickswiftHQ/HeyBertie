<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->integer('monthly_price_pence')->default(0);
            $table->integer('staff_limit')->default(0);
            $table->integer('location_limit')->default(1);
            $table->integer('sms_quota')->default(0);
            $table->integer('sort_order')->default(0);
        });

        DB::table('subscription_tiers')->insert([
            ['name' => 'Free', 'slug' => 'free', 'monthly_price_pence' => 0, 'staff_limit' => 0, 'location_limit' => 1, 'sms_quota' => 0, 'sort_order' => 1],
            ['name' => 'Solo', 'slug' => 'solo', 'monthly_price_pence' => 1999, 'staff_limit' => 0, 'location_limit' => 1, 'sms_quota' => 30, 'sort_order' => 2],
            ['name' => 'Salon', 'slug' => 'salon', 'monthly_price_pence' => 4999, 'staff_limit' => 5, 'location_limit' => 3, 'sms_quota' => 100, 'sort_order' => 3],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_tiers');
    }
};
