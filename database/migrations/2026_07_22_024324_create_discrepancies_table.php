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
        Schema::create('discrepancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_id')->constrained('reconciliations')->cascadeOnDelete();
            $table->foreignId('medication_history_id')->nullable()->constrained('medication_histories')->nullOnDelete();
            $table->foreignId('medication_current_id')->nullable()->constrained('medication_currents')->nullOnDelete();
            $table->enum('type', ['Omission', 'Commission', 'Dose_Change', 'Frequency_Change', 'Route_Change', 'Duplication', 'Therapeutic_Duplication', 'Other']);
            $table->enum('severity', ['Critical', 'Major', 'Minor', 'Documentation']);
            $table->enum('clinical_significance', ['High', 'Moderate', 'Low', 'Unknown'])->default('Unknown');
            $table->enum('status', ['Identified', 'Under_Review', 'Resolved', 'Pending_Prescriber', 'Closed'])->default('Identified');
            $table->enum('pharmacist_assessment', ['Unintended', 'Intentional', 'Requires_Clarification'])->nullable();
            $table->text('description')->nullable();
            $table->text('clinical_note')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index('reconciliation_id');
            $table->index('status');
            $table->index('severity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discrepancies');
    }
};
