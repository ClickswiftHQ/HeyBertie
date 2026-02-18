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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            // Customer Details
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();

            // Pet Profile
            $table->string('pet_name');
            $table->string('pet_breed')->nullable();
            $table->enum('pet_size', ['small', 'medium', 'large'])->nullable();
            $table->date('pet_birthday')->nullable();
            $table->text('pet_notes')->nullable();

            // CRM Fields
            $table->integer('loyalty_points')->default(0);
            $table->integer('total_bookings')->default(0);
            $table->decimal('total_spent', 10, 2)->default(0);
            $table->timestamp('last_visit')->nullable();
            $table->timestamp('birthday')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->json('tags')->nullable();

            $table->timestamps();

            $table->index(['business_id', 'is_active']);
            $table->index(['business_id', 'email']);
            $table->index(['business_id', 'phone']);
            $table->unique(['business_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
