<?php

namespace App\Jobs;

use App\Enums\SafetyCheckStatus;
use App\Models\MedicationCurrent;
use App\Services\OpenFdaService;
use App\Services\RxNormService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Normalizes a medication's name via RxNorm, fetches its openFDA label, and
 * records the outcome on the medication itself. Never lets an upstream
 * failure escape — the medication-add flow must never break because of this.
 */
class CheckMedicationSafetyJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public MedicationCurrent $medication)
    {
        //
    }

    public function handle(RxNormService $rxNorm, OpenFdaService $openFda): void
    {
        try {
            $genericName = $rxNorm->normalize($this->medication->medication_name)
                ?? $this->medication->medication_name;

            $result = $openFda->getLabelData($genericName);

            match ($result['status']) {
                'ok' => $this->markComplete($result['label']),
                'not_found' => $this->markUnavailable(),
                'error' => $this->markFailed(),
            };
        } catch (Throwable $exception) {
            Log::error('Medication safety check failed unexpectedly.', [
                'medication_id' => $this->medication->id,
                'message' => $exception->getMessage(),
            ]);

            $this->markFailed();
        }
    }

    /**
     * @param  array<string, mixed>  $label
     */
    private function markComplete(array $label): void
    {
        $this->medication->update([
            'safety_check_status' => SafetyCheckStatus::Complete,
            'safety_label_data' => $label,
            'safety_checked_at' => now(),
        ]);

        SynthesizeMedicationSafetyContextJob::dispatch($this->medication);
    }

    private function markUnavailable(): void
    {
        $this->medication->update([
            'safety_check_status' => SafetyCheckStatus::Unavailable,
            'safety_label_data' => null,
            'safety_checked_at' => now(),
        ]);
    }

    private function markFailed(): void
    {
        $this->medication->update([
            'safety_check_status' => SafetyCheckStatus::Failed,
            'safety_checked_at' => now(),
        ]);
    }
}
