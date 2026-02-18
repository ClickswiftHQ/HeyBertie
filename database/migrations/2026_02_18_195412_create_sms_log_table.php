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
        Schema::create('sms_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');

            // SMS Details
            $table->string('phone_number');
            $table->enum('message_type', ['booking_confirmation', 'reminder_24hr', 'reminder_2hr', 'cancellation', 'custom']);
            $table->text('message_body');

            // Delivery
            $table->string('twilio_sid')->nullable();
            $table->enum('status', ['queued', 'sent', 'delivered', 'failed'])->default('queued');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            // Billing
            $table->decimal('cost', 8, 4)->default(0);
            $table->boolean('charged_to_business')->default(true);

            $table->timestamps();

            $table->index(['business_id', 'created_at']);
            $table->index('booking_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_log');
    }
};
