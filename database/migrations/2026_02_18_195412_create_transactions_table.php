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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');

            // Transaction Details
            $table->enum('type', ['subscription', 'booking_payment', 'booking_deposit', 'platform_fee', 'refund', 'location_addon']);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('GBP');

            // Stripe References
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_charge_id')->nullable();
            $table->string('stripe_invoice_id')->nullable();

            // Status
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->text('description')->nullable();

            $table->timestamps();

            $table->index(['business_id', 'type', 'created_at']);
            $table->index('stripe_payment_intent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
