<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->integer('sort_order')->default(0);
        });

        DB::table('business_roles')->insert([
            ['name' => 'Owner', 'slug' => 'owner', 'sort_order' => 1],
            ['name' => 'Admin', 'slug' => 'admin', 'sort_order' => 2],
            ['name' => 'Staff', 'slug' => 'staff', 'sort_order' => 3],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('business_roles');
    }
};
