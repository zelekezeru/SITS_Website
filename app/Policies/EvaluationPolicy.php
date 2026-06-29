<?php

namespace App\Policies;

use App\Models\Evaluation;
use App\Models\User;

/**
 * Evaluations are scored by managers/executives and acknowledged by the
 * employee. Once the evaluation period is locked, the record is immutable.
 */
class EvaluationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([
            'run evaluations', 'score evaluations', 'finalize evaluations',
            'view own evaluations', 'view executive reports', 'view department reports',
        ]);
    }

    public function view(User $user, Evaluation $evaluation): bool
    {
        if ($user->canAny(['run evaluations', 'score evaluations', 'finalize evaluations', 'view executive reports'])) {
            return true;
        }

        if ($user->can('view department reports')
            && $user->managesDepartment($evaluation->employee?->department_id)) {
            return true;
        }

        return $user->can('view own evaluations') && $this->owns($user, $evaluation);
    }

    public function create(User $user): bool
    {
        return $user->can('run evaluations');
    }

    public function update(User $user, Evaluation $evaluation): bool
    {
        if ($this->periodLocked($evaluation)) {
            return false;
        }

        return $user->canAny(['run evaluations', 'score evaluations']);
    }

    public function score(User $user, Evaluation $evaluation): bool
    {
        return ! $this->periodLocked($evaluation) && $user->can('score evaluations');
    }

    public function finalize(User $user, Evaluation $evaluation): bool
    {
        return ! $this->periodLocked($evaluation) && $user->can('finalize evaluations');
    }

    // ----- Predicates ----------------------------------------------------

    protected function owns(User $user, Evaluation $evaluation): bool
    {
        return $evaluation->employee?->user_id === $user->id;
    }

    protected function periodLocked(Evaluation $evaluation): bool
    {
        return (bool) $evaluation->period?->isLocked();
    }
}
