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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('mrn')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
            $table->string('contact_primary')->nullable();
            $table->string('contact_secondary')->nullable();
            $table->string('email')->nullable();
            $table->string('address_street')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_postcode')->nullable();
            $table->string('address_state')->nullable();
            $table->dateTime('admission_date');
            $table->dateTime('discharge_date')->nullable();
            $table->string('ward')->nullable();
            $table->string('primary_diagnosis')->nullable();
            $table->text('allergies')->nullable();
            $table->text('known_adrs')->nullable();
            $table->enum('renal_function', ['Normal', 'Mild_Impairment', 'Moderate_Impairment', 'Severe_Impairment'])->default('Normal');
            $table->decimal('egfr', 5, 2)->nullable();
            $table->enum('hepatic_function', ['Normal', 'Mild', 'Moderate', 'Severe'])->default('Normal');
            $table->enum('pregnancy_status', ['Unknown', 'Not_Pregnant', 'Pregnant'])->default('Unknown');
            $table->text('notes')->nullable();
            $table->enum('status', ['Active', 'Discharged', 'Archived'])->default('Active');
            $table->enum('risk_level', ['Low', 'Medium', 'High'])->default('Low');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('risk_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
