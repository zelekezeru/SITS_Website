<?php

namespace App\Models;

use App\Enums\RaterType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationRating extends Model
{
    protected $fillable = [
        'evaluation_id',
        'rater_user_id',
        'rater_type',
        'kpi_id',
        'score',
        'comment_en',
        'comment_am',
    ];

    protected $casts = [
        'rater_type' => RaterType::class,
        'score' => 'decimal:2',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_user_id');
    }

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(Kpi::class);
    }
}
