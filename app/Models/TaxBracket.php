<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Ethiopian PIT bands (Proclamation 1395/2025): tax = income × rate − deduction.
 */
class TaxBracket extends Model
{
    protected $fillable = [
        'min_income',
        'max_income',
        'rate',
        'deduction',
        'is_active',
        'effective_from',
    ];

    protected $casts = [
        'min_income' => 'decimal:2',
        'max_income' => 'decimal:2',
        'rate' => 'decimal:2',
        'deduction' => 'decimal:2',
        'is_active' => 'boolean',
        'effective_from' => 'date',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** The bracket an income falls into (max_income null = top band). */
    public function scopeForIncome(Builder $query, float $income): Builder
    {
        return $query->where('min_income', '<=', $income)
            ->where(function (Builder $q) use ($income) {
                $q->whereNull('max_income')->orWhere('max_income', '>=', $income);
            });
    }
}
