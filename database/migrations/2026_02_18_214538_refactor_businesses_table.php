<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropIndex(['owner_user_id', 'subscription_tier']);
            $table->dropColumn(['subscription_tier', 'subscription_status']);
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->foreignId('subscription_tier_id')->after('website')->constrained('subscription_tiers')->onDelete('restrict');
            $table->foreignId('subscription_status_id')->after('subscription_tier_id')->constrained('subscription_statuses')->onDelete('restrict');
            $table->index(['owner_user_id', 'subscription_tier_id']);
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropIndex(['owner_user_id', 'subscription_tier_id']);
            $table->dropForeign(['subscription_tier_id']);
            $table->dropForeign(['subscription_status_id']);
            $table->dropColumn(['subscription_tier_id', 'subscription_status_id']);
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->enum('subscription_tier', ['free', 'solo', 'salon'])->default('free')->after('website');
            $table->enum('subscription_status', ['trial', 'active', 'past_due', 'cancelled', 'suspended'])->default('trial')->after('subscription_tier');
            $table->index(['owner_user_id', 'subscription_tier']);
        });
    }
};
