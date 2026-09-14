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
        Schema::table('medication_currents', function (Blueprint $table) {
            $table->string('safety_check_status')->default('pending');
            $table->json('safety_label_data')->nullable();
            $table->json('safety_flags')->nullable();
            $table->timestamp('safety_checked_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medication_currents', function (Blueprint $table) {
            $table->dropColumn(['safety_check_status', 'safety_label_data', 'safety_flags', 'safety_checked_at']);
        });
    }
};
