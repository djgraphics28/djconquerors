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
            $table->string('riscoin_id')->nullable()->after('email');
            $table->string('inviters_code')->nullable()->after('riscoin_id');
            $table->decimal('invested_amount', 15, 2)->nullable()->default(0)->after('inviters_code');
            $table->boolean('is_active')->nullable()->default(true)->after('invested_amount');
            $table->date('date_joined')->nullable()->after('is_active');
            $table->date('birth_date')->nullable()->after('date_joined');
            $table->string('phone_number')->nullable()->after('birth_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('riscoin_id');
            $table->dropColumn('inviters_code');
            $table->dropColumn('invested_amount');
            $table->dropColumn('is_active');
        });
    }
};
