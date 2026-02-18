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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('cascade');

            // Service Details
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('duration_minutes');

            // Pricing
            $table->decimal('price', 8, 2)->nullable();
            $table->enum('price_type', ['fixed', 'from', 'call'])->default('fixed');

            // Display
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);

            $table->timestamps();

            $table->index(['business_id', 'is_active', 'display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
