<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_user', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('business_user', function (Blueprint $table) {
            $table->foreignId('business_role_id')->after('user_id')->constrained()->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('business_user', function (Blueprint $table) {
            $table->dropForeign(['business_role_id']);
            $table->dropColumn('business_role_id');
        });

        Schema::table('business_user', function (Blueprint $table) {
            $table->enum('role', ['owner', 'admin', 'staff'])->default('staff')->after('user_id');
        });
    }
};
