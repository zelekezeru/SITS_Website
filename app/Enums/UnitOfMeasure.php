<?php

namespace App\Enums;

/**
 * How an item is counted. Deliberately a closed set: free-text units are how
 * "pcs", "Pcs", "piece" and "pieces" end up as four incomparable units in the
 * same report.
 *
 * `isDivisible()` decides whether the ledger accepts fractional quantities —
 * 2.5 litres of fuel is meaningful, 2.5 chairs is a data-entry error.
 */
enum UnitOfMeasure: string
{
    case Piece  = 'piece';
    case Set    = 'set';
    case Pair   = 'pair';
    case Box    = 'box';
    case Carton = 'carton';
    case Packet = 'packet';
    case Ream   = 'ream';
    case Bundle = 'bundle';
    case Roll   = 'roll';
    case Bottle = 'bottle';
    case Sachet = 'sachet';
    case Litre  = 'litre';
    case Kilogram = 'kilogram';
    case Gram   = 'gram';
    case Metre  = 'metre';
    case Bag    = 'bag';
    case Dozen  = 'dozen';

    public function label(): string
    {
        return match ($this) {
            self::Piece => 'Piece', self::Set => 'Set', self::Pair => 'Pair',
            self::Box => 'Box', self::Carton => 'Carton', self::Packet => 'Packet',
            self::Ream => 'Ream', self::Bundle => 'Bundle', self::Roll => 'Roll',
            self::Bottle => 'Bottle', self::Sachet => 'Sachet', self::Litre => 'Litre',
            self::Kilogram => 'Kilogram', self::Gram => 'Gram', self::Metre => 'Metre',
            self::Bag => 'Bag', self::Dozen => 'Dozen',
        };
    }

    /** Short form for tables and labels. */
    public function abbreviation(): string
    {
        return match ($this) {
            self::Piece => 'pc', self::Set => 'set', self::Pair => 'pr',
            self::Box => 'box', self::Carton => 'ctn', self::Packet => 'pkt',
            self::Ream => 'rm', self::Bundle => 'bdl', self::Roll => 'roll',
            self::Bottle => 'btl', self::Sachet => 'sct', self::Litre => 'L',
            self::Kilogram => 'kg', self::Gram => 'g', self::Metre => 'm',
            self::Bag => 'bag', self::Dozen => 'dz',
        };
    }

    /** Whether a fractional quantity is meaningful for this unit. */
    public function isDivisible(): bool
    {
        return in_array($this, [self::Litre, self::Kilogram, self::Gram, self::Metre], true);
    }

    /** @return array<string, string> ['value' => 'Label (abbr)'] for selects. */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $c) => $carry + [$c->value => $c->label().' ('.$c->abbreviation().')'],
            []
        );
    }
}
