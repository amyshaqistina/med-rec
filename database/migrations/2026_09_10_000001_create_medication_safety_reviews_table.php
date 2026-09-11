<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_safety_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('completed');
            $table->string('input_hash', 64);
            $table->json('input_snapshot');
            $table->json('results')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['reconciliation_id', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_safety_reviews');
    }
};
