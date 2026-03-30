<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the hardcoded enum `classification` column with a proper
     * foreign key to `guide_options`, making the system fully dynamic.
     */
    public function up(): void
    {
        Schema::table('guides', function (Blueprint $table) {
            $table->dropColumn('classification');
        });

        Schema::table('guides', function (Blueprint $table) {
            $table->foreignId('guide_option_id')
                ->after('slug')
                ->nullable()
                ->constrained('guide_options')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('guides', function (Blueprint $table) {
            $table->dropForeign(['guide_option_id']);
            $table->dropColumn('guide_option_id');
        });

        Schema::table('guides', function (Blueprint $table) {
            $table->enum('classification', [
                'rules', 'bonchat', 'riscoin', 'binance',
                'okx', 'gcash', 'maya', 'ios', 'android',
            ])->after('slug')->nullable();
        });
    }
};
