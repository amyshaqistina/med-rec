<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $department
 * @property int $bed_capacity
 * @property string $color
 */
#[Fillable(['name', 'department', 'bed_capacity', 'color'])]
class Ward extends Model
{
    /** @use HasFactory<\Database\Factories\WardFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'bed_capacity' => 'integer',
        ];
    }

    /**
     * @return HasMany<Patient, $this>
     */
    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    /**
     * Tailwind classes for the ward's icon badge. Written as complete literal
     * strings (not interpolated) so Tailwind's content scanner can detect them.
     */
    public function iconClasses(): string
    {
        return match ($this->color) {
            'red' => 'bg-red-100 text-red-600 dark:bg-red-400/10 dark:text-red-400',
            'emerald' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400',
            'blue' => 'bg-blue-100 text-blue-600 dark:bg-blue-400/10 dark:text-blue-400',
            'purple' => 'bg-purple-100 text-purple-600 dark:bg-purple-400/10 dark:text-purple-400',
            'amber' => 'bg-amber-100 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400',
            'teal' => 'bg-teal-100 text-teal-600 dark:bg-teal-400/10 dark:text-teal-400',
            'orange' => 'bg-orange-100 text-orange-600 dark:bg-orange-400/10 dark:text-orange-400',
            'pink' => 'bg-pink-100 text-pink-600 dark:bg-pink-400/10 dark:text-pink-400',
            'cyan' => 'bg-cyan-100 text-cyan-600 dark:bg-cyan-400/10 dark:text-cyan-400',
            'lime' => 'bg-lime-100 text-lime-600 dark:bg-lime-400/10 dark:text-lime-400',
            default => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-400/10 dark:text-zinc-400',
        };
    }
}
