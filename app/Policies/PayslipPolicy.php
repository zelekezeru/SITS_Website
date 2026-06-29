<?php

namespace App\Policies;

use App\Models\Payslip;
use App\Models\User;

/**
 * Payroll staff manage all payslips; employees may view only their own.
 * Payslips in a locked/paid payroll period are immutable.
 */
class PayslipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny(['manage payroll', 'view payslips']);
    }

    public function view(User $user, Payslip $payslip): bool
    {
        if ($user->can('manage payroll')) {
            return true;
        }

        return $user->can('view payslips') && $payslip->employee?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('manage payroll');
    }

    public function update(User $user, Payslip $payslip): bool
    {
        return $user->can('manage payroll') && ! (bool) $payslip->payrollPeriod?->isLocked();
    }

    public function delete(User $user, Payslip $payslip): bool
    {
        return $user->can('manage payroll') && ! (bool) $payslip->payrollPeriod?->isLocked();
    }
}
