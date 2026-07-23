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
        Schema::create('medication_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('reconciliation_id')->nullable()->constrained('reconciliations')->nullOnDelete();
            $table->string('medication_name');
            $table->string('strength')->nullable();
            $table->decimal('dose_amount', 10, 2)->nullable();
            $table->string('dose_unit')->nullable();
            $table->enum('route', ['PO', 'IV', 'IM', 'SC', 'Topical', 'Inhaled', 'Other'])->nullable();
            $table->string('frequency')->nullable();
            $table->string('timing')->nullable();
            $table->string('indication')->nullable();
            $table->date('start_date')->nullable();
            $table->string('prescriber_name')->nullable();
            $table->enum('is_patient_taking', ['Yes', 'No', 'Not_Sure'])->default('Yes');
            $table->enum('adherence_level', ['Full', 'Partial', 'None', 'Unknown'])->nullable();
            $table->string('non_adherence_reason')->nullable();
            $table->enum('source_type', ['Patient_Report', 'Family', 'Med_Bottle', 'Previous_Record', 'Pharmacy', 'Other'])->nullable();
            $table->enum('reliability_rating', ['Definite', 'Probable', 'Possible'])->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('patient_id');
            $table->index('reconciliation_id');
            $table->index('is_patient_taking');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medication_histories');
    }
};
