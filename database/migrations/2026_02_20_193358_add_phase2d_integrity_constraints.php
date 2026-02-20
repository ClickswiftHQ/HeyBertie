<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->unique('stripe_customer_id');
            $table->unique('stripe_subscription_id');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('staff_members', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropUnique(['stripe_customer_id']);
            $table->dropUnique(['stripe_subscription_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
