<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['business_id', 'status', 'appointment_datetime']);
            $table->index(['business_id', 'appointment_datetime']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['business_id', 'status', 'created_at']);
        });

        Schema::table('sms_log', function (Blueprint $table) {
            $table->index(['business_id', 'status', 'created_at']);
        });

        Schema::table('email_log', function (Blueprint $table) {
            $table->index(['business_id', 'status', 'created_at']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['business_id', 'is_flagged']);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'status', 'appointment_datetime']);
            $table->dropIndex(['business_id', 'appointment_datetime']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'status', 'created_at']);
        });

        Schema::table('sms_log', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'status', 'created_at']);
        });

        Schema::table('email_log', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'status', 'created_at']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'is_flagged']);
        });
    }
};
