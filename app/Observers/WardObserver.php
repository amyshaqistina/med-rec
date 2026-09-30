<?php

namespace App\Observers;

use App\Models\Bed;
use App\Models\Ward;

class WardObserver
{
    /**
     * Generate the ward's beds (numbered 1..bed_capacity) as soon as it's created.
     */
    public function created(Ward $ward): void
    {
        if ($ward->bed_capacity < 1) {
            return;
        }

        $now = now();

        $beds = collect(range(1, $ward->bed_capacity))->map(fn (int $bedNo) => [
            'ward_id' => $ward->id,
            'bed_no' => $bedNo,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Bed::insert($beds->all());
    }
}
