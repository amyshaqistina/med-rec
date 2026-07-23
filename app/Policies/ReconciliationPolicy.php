<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Reconciliation;
use App\Models\User;

class ReconciliationPolicy
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
    public function view(User $user, Reconciliation $reconciliation): bool
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
     * Determine whether the user can update the model (entering current-meds list, running discrepancy detection).
     */
    public function update(User $user, Reconciliation $reconciliation): bool
    {
        return $user->hasAnyRole(UserRole::Technician, UserRole::Pharmacist, UserRole::Manager, UserRole::Admin);
    }

    /**
     * Determine whether the user can assess discrepancies and complete verification.
     * Also governs Discrepancy assessment/status/clinical-note edits (no separate DiscrepancyPolicy).
     */
    public function pharmacistAssess(User $user, Reconciliation $reconciliation): bool
    {
        return $user->hasAnyRole(UserRole::Pharmacist, UserRole::Manager, UserRole::Admin);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Reconciliation $reconciliation): bool
    {
        return $user->hasAnyRole(UserRole::Manager, UserRole::Admin);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Reconciliation $reconciliation): bool
    {
        return $user->hasAnyRole(UserRole::Manager, UserRole::Admin);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Reconciliation $reconciliation): bool
    {
        return $user->hasAnyRole(UserRole::Admin);
    }
}
