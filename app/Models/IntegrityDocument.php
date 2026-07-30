<?php

namespace App\Models;

use App\Enums\IntegrityDocumentSource;
use App\Enums\IntegrityDocumentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class IntegrityDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'instructor_id',
        'student_id',
        'course_id',
        'title',
        'original_filename',
        'mime_type',
        'source',
        'word_count',
        'language',
        'extracted_text',
        'status',
        'failure_reason',
    ];

    protected $casts = [
        'source' => IntegrityDocumentSource::class,
        'status' => IntegrityDocumentStatus::class,
        'word_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $document) {
            if (empty($document->uuid)) {
                $document->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function report(): HasOne
    {
        return $this->hasOne(IntegrityReport::class);
    }

    public function plagiarismReports(): HasMany
    {
        return $this->hasMany(PlagiarismReport::class);
    }

    public function fingerprints(): HasMany
    {
        return $this->hasMany(CorpusFingerprint::class);
    }

    public function writingReports(): HasMany
    {
        return $this->hasMany(WritingReport::class);
    }

    /** Documents owned by the given instructor. */
    public function scopeForInstructor($query, User $user)
    {
        return $query->where('instructor_id', $user->id);
    }
}
