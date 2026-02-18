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
        Schema::create('email_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');

            // Email Details
            $table->string('to_email');
            $table->enum('email_type', ['welcome', 'booking_confirmation', 'reminder', 'cancellation', 'review_request', 'invoice', 'custom']);
            $table->string('subject');

            // Delivery
            $table->string('postmark_message_id')->nullable();
            $table->enum('status', ['queued', 'sent', 'delivered', 'bounced', 'failed'])->default('queued');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();

            $table->timestamps();

            $table->index(['business_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_log');
    }
};
