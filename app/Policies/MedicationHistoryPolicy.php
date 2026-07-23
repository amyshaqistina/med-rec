<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\MedicationHistory;
use App\Models\User;

class MedicationHistoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MedicationHistory $medicationHistory): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(UserRole::Technician, UserRole::Pharmacist, UserRole::Manager, UserRole::Admin);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MedicationHistory $medicationHistory): bool
    {
        return $user->hasAnyRole(UserRole::Technician, UserRole::Pharmacist, UserRole::Manager, UserRole::Admin);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MedicationHistory $medicationHistory): bool
    {
        return $user->hasAnyRole(UserRole::Manager, UserRole::Admin);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MedicationHistory $medicationHistory): bool
    {
        return $user->hasAnyRole(UserRole::Manager, UserRole::Admin);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MedicationHistory $medicationHistory): bool
    {
        return $user->hasAnyRole(UserRole::Admin);
    }
}
