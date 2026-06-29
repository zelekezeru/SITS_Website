<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Generalized, audited key/value store for payroll/scoring constants and app config.
 * Values are stored as text and cast to their declared `type` on read.
 */
class Setting extends Model
{
    use Blameable;

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** The stored value cast to its declared type. */
    protected function typedValue(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->castValue($this->value),
        );
    }

    protected function castValue(?string $raw): mixed
    {
        return match ($this->type) {
            'integer' => $raw === null ? null : (int) $raw,
            'decimal' => $raw === null ? null : (float) $raw,
            'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'json' => $raw === null ? null : json_decode($raw, true),
            default => $raw,
        };
    }

    /** Fetch a setting's typed value by key. */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->typed_value : $default;
    }

    /** Create or update a setting (group/type preserved when not given). */
    public static function set(string $key, mixed $value, ?string $group = null, ?string $type = null): self
    {
        $setting = static::firstOrNew(['key' => $key]);

        if ($group !== null) {
            $setting->group = $group;
        }
        if ($type !== null) {
            $setting->type = $type;
        }

        $setting->value = is_array($value) ? json_encode($value) : (string) $value;
        $setting->save();

        return $setting;
    }
}
