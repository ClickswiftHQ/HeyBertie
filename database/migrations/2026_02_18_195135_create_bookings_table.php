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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_member_id')->nullable()->constrained()->onDelete('set null');

            // Appointment Details
            $table->dateTime('appointment_datetime');
            $table->integer('duration_minutes');
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])->default('pending');

            // Pricing
            $table->decimal('price', 8, 2);
            $table->decimal('deposit_amount', 8, 2)->default(0);
            $table->boolean('deposit_paid')->default(false);
            $table->enum('payment_status', ['pending', 'deposit_paid', 'paid', 'refunded'])->default('pending');
            $table->string('payment_intent_id')->nullable();

            // Notes
            $table->text('customer_notes')->nullable();
            $table->text('pro_notes')->nullable();

            // Reminders
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('reminder_2hr_sent_at')->nullable();

            // Cancellation
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();

            $table->index(['location_id', 'appointment_datetime']);
            $table->index(['staff_member_id', 'appointment_datetime']);
            $table->index(['customer_id', 'appointment_datetime']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
