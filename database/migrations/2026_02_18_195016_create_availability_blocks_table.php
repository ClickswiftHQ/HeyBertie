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
        Schema::create('availability_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('staff_member_id')->nullable()->constrained()->onDelete('cascade');

            // Recurring Schedule
            $table->integer('day_of_week')->nullable(); // 0=Sunday, 6=Saturday
            $table->time('start_time');
            $table->time('end_time');

            // One-off Date
            $table->date('specific_date')->nullable();

            // Block Type
            $table->enum('block_type', ['available', 'break', 'blocked', 'holiday'])->default('available');

            $table->boolean('repeat_weekly')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['location_id', 'day_of_week']);
            $table->index(['staff_member_id', 'day_of_week']);
            $table->index('specific_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availability_blocks');
    }
};
