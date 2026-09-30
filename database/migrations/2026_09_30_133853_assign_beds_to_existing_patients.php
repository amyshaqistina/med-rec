<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Give every active ward patient left without a bed (the old `bed_no` column was dropped
     * without being carried over) the lowest-numbered free bed in their ward.
     */
    public function up(): void
    {
        DB::table('patients')
            ->where('status', 'Active')
            ->whereNotNull('ward_id')
            ->whereNull('bed_id')
            ->orderBy('id')
            ->get(['id', 'ward_id'])
            ->groupBy('ward_id')
            ->each(function ($patients, $wardId) {
                $freeBedIds = DB::table('beds')
                    ->where('ward_id', $wardId)
                    ->whereNotIn('id', DB::table('patients')->whereNotNull('bed_id')->select('bed_id'))
                    ->orderBy('bed_no')
                    ->pluck('id');

                $patients->values()->each(function ($patient, int $index) use ($freeBedIds) {
                    if (! isset($freeBedIds[$index])) {
                        return false;
                    }

                    DB::table('patients')->where('id', $patient->id)->update(['bed_id' => $freeBedIds[$index]]);
                });
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
