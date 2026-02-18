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
        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Staff Details
            $table->string('display_name');
            $table->text('bio')->nullable();
            $table->string('photo_url')->nullable();

            // Employment
            $table->enum('role', ['groomer', 'assistant', 'receptionist'])->default('groomer');
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->string('calendar_color', 7)->default('#6B7280');

            // Availability
            $table->json('working_locations')->nullable();
            $table->boolean('accepts_online_bookings')->default(true);

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('employed_since')->nullable();
            $table->timestamp('left_at')->nullable();

            $table->timestamps();

            $table->index(['business_id', 'is_active']);
            $table->unique(['business_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_members');
    }
};
