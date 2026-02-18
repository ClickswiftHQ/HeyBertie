<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['pet_name', 'pet_breed', 'pet_size', 'pet_birthday', 'pet_notes']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('source')->default('online')->after('address');
            $table->boolean('marketing_consent')->default(false)->after('source');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['source', 'marketing_consent']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('pet_name')->after('address');
            $table->string('pet_breed')->nullable()->after('pet_name');
            $table->enum('pet_size', ['small', 'medium', 'large'])->nullable()->after('pet_breed');
            $table->date('pet_birthday')->nullable()->after('pet_size');
            $table->text('pet_notes')->nullable()->after('pet_birthday');
        });
    }
};
