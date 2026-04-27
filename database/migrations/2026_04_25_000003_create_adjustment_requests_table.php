<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adjustment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Requested corrections (all nullable — user only fills what needs changing)
            $table->string('correct_name')->nullable();
            $table->string('correct_riscoin_id')->nullable();
            $table->string('correct_inviters_code')->nullable();
            $table->foreignId('correct_assistant_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();

            // Review
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjustment_requests');
    }
};
