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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');

            // Location Identity
            $table->string('name');
            $table->string('slug');
            $table->enum('location_type', ['salon', 'mobile', 'home_based'])->default('salon');

            // Address
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('postcode');
            $table->string('county')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Mobile Service
            $table->boolean('is_mobile')->default(false);
            $table->integer('service_radius_km')->nullable();

            // Contact
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // Settings
            $table->json('opening_hours')->nullable();
            $table->integer('booking_buffer_minutes')->default(15);
            $table->integer('advance_booking_days')->default(60);
            $table->integer('min_notice_hours')->default(24);

            // Status
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('accepts_bookings')->default(true);

            $table->timestamps();

            $table->index(['business_id', 'is_active']);
            $table->index(['city', 'postcode']);
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
