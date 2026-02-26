<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename stripe_customer_id → stripe_id (idempotent)
        if (Schema::hasColumn('businesses', 'stripe_customer_id')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->renameColumn('stripe_customer_id', 'stripe_id');
            });
        }

        // Drop stripe_subscription_id (idempotent)
        if (Schema::hasColumn('businesses', 'stripe_subscription_id')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->dropUnique('businesses_stripe_subscription_id_unique');
            });

            Schema::table('businesses', function (Blueprint $table) {
                $table->dropColumn('stripe_subscription_id');
            });
        }

        // Add Cashier columns to businesses
        if (! Schema::hasColumn('businesses', 'pm_type')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->string('pm_type')->nullable()->after('stripe_id');
                $table->string('pm_last_four', 4)->nullable()->after('pm_type');
            });
        }

        // Add columns to subscription_tiers
        if (! Schema::hasColumn('subscription_tiers', 'stripe_price_id')) {
            Schema::table('subscription_tiers', function (Blueprint $table) {
                $table->string('stripe_price_id')->nullable()->after('slug');
                $table->integer('trial_days')->default(0)->after('sms_quota');
            });

            // Seed trial_days for existing tiers
            DB::table('subscription_tiers')->where('slug', 'solo')->update(['trial_days' => 14]);
            DB::table('subscription_tiers')->where('slug', 'salon')->update(['trial_days' => 14]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_tiers', function (Blueprint $table) {
            $table->dropColumn(['stripe_price_id', 'trial_days']);
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['pm_type', 'pm_last_four']);
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->string('stripe_subscription_id')->nullable()->unique();
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->renameColumn('stripe_id', 'stripe_customer_id');
        });
    }
};
