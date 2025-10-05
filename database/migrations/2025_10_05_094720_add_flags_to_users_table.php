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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_birthday_mention')->default(true)->after('remember_token');
            $table->boolean('is_monthly_milestone_mention')->default(true)->after('is_birthday_mention');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_birthday_mention');
            $table->dropColumn('is_monthly_milestone_mention');
        });
    }
};
