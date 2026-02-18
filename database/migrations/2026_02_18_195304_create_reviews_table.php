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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Review Content
            $table->integer('rating');
            $table->text('review_text')->nullable();
            $table->json('photos')->nullable();

            // Verification
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_published')->default(true);

            // Response
            $table->text('response_text')->nullable();
            $table->foreignId('responded_by_user_id')->nullable()->constrained('users');
            $table->timestamp('responded_at')->nullable();

            // Moderation
            $table->boolean('is_flagged')->default(false);
            $table->text('flag_reason')->nullable();

            $table->timestamps();

            $table->index(['business_id', 'is_published', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
