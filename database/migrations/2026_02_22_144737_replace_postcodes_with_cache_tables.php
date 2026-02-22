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
        Schema::dropIfExists('postcodes');

        Schema::create('geocode_cache', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('display_name');
            $table->string('slug')->unique();
            $table->string('postcode_sector')->default('')->index();
            $table->string('county')->default('');
            $table->string('country')->default('');
            $table->string('settlement_type')->default('');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->timestamps();

            $table->unique(['name', 'county', 'postcode_sector']);
            $table->index(['latitude', 'longitude']);
        });

        Schema::create('address_cache', function (Blueprint $table) {
            $table->id();
            $table->string('postcode')->index();
            $table->string('line_1');
            $table->string('line_2')->default('');
            $table->string('line_3')->default('');
            $table->string('post_town');
            $table->string('county')->default('');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->timestamps();

            $table->unique(['postcode', 'line_1']);
        });

        Schema::create('unmatched_searches', function (Blueprint $table) {
            $table->id();
            $table->string('query')->unique();
            $table->integer('search_count')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unmatched_searches');
        Schema::dropIfExists('address_cache');
        Schema::dropIfExists('geocode_cache');

        Schema::create('postcodes', function (Blueprint $table) {
            $table->string('postcode')->primary();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('town')->nullable();
            $table->string('county')->nullable();
            $table->string('region')->nullable();

            $table->index(['latitude', 'longitude']);
        });
    }
};
