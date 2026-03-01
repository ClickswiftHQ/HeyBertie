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
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('stripe_connect_id')->nullable()->unique()->after('stripe_id');
            $table->boolean('stripe_connect_onboarding_complete')->default(false)->after('stripe_connect_id');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['stripe_connect_id', 'stripe_connect_onboarding_complete']);
        });
    }
};
