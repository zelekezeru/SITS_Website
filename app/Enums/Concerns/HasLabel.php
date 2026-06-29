<?php

namespace App\Enums\Concerns;

trait HasLabel
{
    /** Human-readable label derived from the backing value. */
    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }

    /** ['value' => 'Label'] map, handy for <select> options. */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $case) => $carry + [$case->value => $case->label()],
            []
        );
    }
}
