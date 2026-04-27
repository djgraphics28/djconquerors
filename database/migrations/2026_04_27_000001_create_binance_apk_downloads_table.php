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
        Schema::create('binance_apk_downloads', function (Blueprint $table) {
            $table->id();
            $table->string('title')->default('Binance for Android');
            $table->text('description')->nullable();
            $table->string('download_url');
            $table->string('version')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('binance_apk_downloads');
    }
};
