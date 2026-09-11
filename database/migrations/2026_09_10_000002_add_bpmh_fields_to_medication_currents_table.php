<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medication_currents', function (Blueprint $table) {
            $table->string('strength')->nullable()->after('medication_name');
            $table->decimal('dose_amount', 10, 2)->nullable()->after('strength');
            $table->string('dose_unit', 50)->nullable()->after('dose_amount');
            $table->string('timing')->nullable()->after('frequency');
            $table->enum('is_patient_taking', ['Yes', 'No', 'Not_Sure'])->nullable()->after('indication');
            $table->enum('adherence_level', ['Full', 'Partial', 'None', 'Unknown'])->nullable()->after('is_patient_taking');
            $table->string('non_adherence_reason')->nullable()->after('adherence_level');
            $table->enum('source_type', ['Patient_Report', 'Family', 'Med_Bottle', 'Previous_Record', 'Pharmacy', 'Other'])->nullable()->after('non_adherence_reason');
        });
    }

    public function down(): void
    {
        Schema::table('medication_currents', function (Blueprint $table) {
            $table->dropColumn([
                'strength', 'dose_amount', 'dose_unit', 'timing', 'is_patient_taking',
                'adherence_level', 'non_adherence_reason', 'source_type',
            ]);
        });
    }
};
