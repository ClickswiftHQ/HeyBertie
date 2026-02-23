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
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('booking_reference')->unique()->nullable()->after('status');
            $table->string('pet_name')->nullable()->after('customer_notes');
            $table->string('pet_breed')->nullable()->after('pet_name');
            $table->string('pet_size')->nullable()->after('pet_breed');
        });

        // Make service_id nullable for multi-service bookings
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('service_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['booking_reference', 'pet_name', 'pet_breed', 'pet_size']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('service_id')->nullable(false)->change();
        });
    }
};
