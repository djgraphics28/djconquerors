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
        Schema::create('calculator_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('calculator_type'); // 'compound_interest', etc.
            $table->decimal('invested_amount', 15, 2)->nullable();
            $table->decimal('first_reward', 15, 2)->nullable();
            $table->integer('signals_per_day')->nullable();
            $table->integer('number_of_days')->nullable();
            $table->boolean('is_first_time')->default(false);
            $table->decimal('final_amount', 15, 2)->nullable();
            $table->json('calculation_data')->nullable(); // Store full calculation results
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calculator_usage_logs');
    }
};
