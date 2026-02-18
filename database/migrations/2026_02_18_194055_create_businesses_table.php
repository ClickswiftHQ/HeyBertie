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
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();

            // Basic Info
            $table->string('name');
            $table->string('handle')->unique();
            $table->string('slug');
            $table->text('description')->nullable();

            // Branding
            $table->string('logo_url')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            // Subscription
            $table->enum('subscription_tier', ['free', 'solo', 'salon'])->default('free');
            $table->enum('subscription_status', ['trial', 'active', 'past_due', 'cancelled', 'suspended'])->default('trial');
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();

            // Verification
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('verification_notes')->nullable();
            $table->timestamp('verified_at')->nullable();

            // Ownership
            $table->foreignId('owner_user_id')->constrained('users')->onDelete('cascade');

            // Metadata
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('handle');
            $table->index(['owner_user_id', 'subscription_tier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
