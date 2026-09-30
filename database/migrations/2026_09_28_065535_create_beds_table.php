<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('beds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ward_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('bed_no');
            $table->timestamps();

            $table->unique(['ward_id', 'bed_no']);
        });

        $this->backfillBedsForExistingWards();
    }

    /**
     * Generate beds 1..bed_capacity for every ward that already exists, so wards created
     * before this migration (e.g. by the seeder) don't end up with zero beds.
     */
    private function backfillBedsForExistingWards(): void
    {
        DB::table('wards')->select('id', 'bed_capacity')->orderBy('id')->cursor()->each(function ($ward) {
            $now = now();

            $beds = collect(range(1, $ward->bed_capacity))->map(fn (int $bedNo) => [
                'ward_id' => $ward->id,
                'bed_no' => $bedNo,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('beds')->insert($beds->all());
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beds');
    }
};
