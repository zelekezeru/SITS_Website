<?php

namespace App\Policies;

use App\Models\Kpi;
use App\Models\User;

/**
 * KPI maker-checker: created -> approved (dept head, "approve kpis")
 * -> confirmed (admin/president, "confirm kpis"). Confirmed KPIs are
 * locked from edits/deletes (only Super Admin bypasses, via Gate::before).
 */
class KpiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny(['view kpis', 'crud kpis', 'approve kpis', 'confirm kpis']);
    }

    public function view(User $user, Kpi $kpi): bool
    {
        if ($user->canAny(['view kpis', 'crud kpis', 'approve kpis', 'confirm kpis'])) {
            return true;
        }

        // Employees may see KPIs assigned to them.
        return $kpi->employees()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('crud kpis');
    }

    public function update(User $user, Kpi $kpi): bool
    {
        // Once confirmed, a KPI is immutable to everyone but the Super Admin.
        return $user->can('crud kpis') && ! $kpi->isConfirmed();
    }

    public function delete(User $user, Kpi $kpi): bool
    {
        return $user->can('crud kpis') && ! $kpi->isConfirmed();
    }

    /** Maker step — department head approves a created KPI. */
    public function approve(User $user, Kpi $kpi): bool
    {
        return $user->can('approve kpis') && ! $kpi->isApproved();
    }

    /** Checker step — admin/president confirms an approved KPI. */
    public function confirm(User $user, Kpi $kpi): bool
    {
        return $user->can('confirm kpis') && $kpi->isApproved() && ! $kpi->isConfirmed();
    }
}
