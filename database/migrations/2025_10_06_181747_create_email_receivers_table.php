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
        Schema::create('email_receivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('receive_appointment_notifications')->default(true);
            $table->boolean('receive_system_notifications')->default(false);
            $table->boolean('receive_user_registrations')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('custom_settings')->nullable(); // JSON field for additional settings
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_receivers');
    }
};
