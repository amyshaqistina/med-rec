<?php

namespace Database\Seeders;

use App\Models\Medication;
use Illuminate\Database\Seeder;

class MedicationSeeder extends Seeder
{
    /**
     * Common medication names offered as autocomplete suggestions across the app.
     * Free text is still accepted wherever this list is used.
     *
     * @var array<int, string>
     */
    private const MEDICATIONS = [
        'Amlodipine', 'Lisinopril', 'Losartan', 'Metoprolol', 'Atenolol', 'Bisoprolol',
        'Hydrochlorothiazide', 'Furosemide', 'Spironolactone', 'Atorvastatin', 'Simvastatin',
        'Rosuvastatin', 'Metformin', 'Gliclazide', 'Insulin Glargine', 'Insulin Aspart',
        'Aspirin', 'Clopidogrel', 'Warfarin', 'Apixaban', 'Rivaroxaban', 'Omeprazole',
        'Pantoprazole', 'Ranitidine', 'Paracetamol', 'Ibuprofen', 'Diclofenac', 'Tramadol',
        'Codeine', 'Morphine', 'Amoxicillin', 'Amoxicillin-Clavulanate', 'Ciprofloxacin',
        'Azithromycin', 'Doxycycline', 'Metronidazole', 'Cephalexin', 'Prednisolone',
        'Salbutamol', 'Ipratropium', 'Budesonide', 'Montelukast', 'Levothyroxine',
        'Carbimazole', 'Sertraline', 'Fluoxetine', 'Amitriptyline', 'Diazepam', 'Zolpidem',
        'Gabapentin', 'Pregabalin', 'Phenytoin', 'Sodium Valproate', 'Levetiracetam',
        'Digoxin', 'Amiodarone', 'Isosorbide Mononitrate', 'Glyceryl Trinitrate',
        'Allopurinol', 'Colchicine', 'Folic Acid', 'Ferrous Sulfate', 'Vitamin D3',
        'Calcium Carbonate', 'Multivitamin',
    ];

    /**
     * Seed the medication reference list.
     */
    public function run(): void
    {
        $now = now();

        Medication::query()->insertOrIgnore(
            collect(self::MEDICATIONS)
                ->map(fn (string $name) => ['name' => $name, 'created_at' => $now, 'updated_at' => $now])
                ->all()
        );
    }
}
