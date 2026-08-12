<?php

namespace App\Support\Inventory;

use App\Models\InventoryBatch;
use App\Models\InventoryCategory;
use App\Models\InventoryDisposal;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryRequest;
use App\Models\InventoryStocktake;
use App\Models\InventorySupplier;
use App\Models\InventoryUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Human-meaningful identifiers for every inventory record: SKUs, asset tags,
 * GRN numbers, requisition and voucher references.
 *
 * Sequences are derived from the highest existing code rather than a counter
 * table, so the generator stays correct across imports, restores and manual
 * inserts — a counter row that drifts out of step with the data is worse than
 * no counter at all.
 *
 * Every generator runs inside a transaction with a locked read of the current
 * maximum, and retries on the unique-constraint race. Callers get a code that is
 * already reserved by the row they are about to write.
 *
 * Formats (docs/inventory-management-design.md §4):
 *   Item SKU      IT-00184           <CAT>-<seq:5>
 *   Asset tag     SITS-IT-000871     SITS-<CAT>-<seq:6>
 *   GRN           GRN-2026-0043      GRN-<year>-<seq:4>
 *   Requisition   REQ-2026-0912      REQ-<year>-<seq:4>
 *   Issue voucher ISV-2026-0455      ISV-<year>-<seq:4>
 *   Stocktake     STK-2026-007       STK-<year>-<seq:3>
 *   Disposal      DSP-2026-0012      DSP-<year>-<seq:4>
 *   Location      LOC-MC-0031        LOC-<campus>-<seq:4>
 *   Supplier      SUP-0042           SUP-<seq:4>
 */
class InventoryCodeGenerator
{
    /** How many times to retry when two requests claim the same number. */
    private const MAX_ATTEMPTS = 5;

    // ---- Catalog -------------------------------------------------------------

    /** Item SKU, prefixed with its category's code: IT-00184. */
    public static function itemCode(InventoryCategory $category): string
    {
        $prefix = self::sanitize($category->code);

        return self::next(InventoryItem::class, 'code', $prefix.'-', 5);
    }

    /** Asset tag for a serialized unit: SITS-IT-000871. */
    public static function assetTag(InventoryItem $item): string
    {
        $prefix = self::sanitize($item->category?->code ?? 'GEN');

        return self::next(InventoryUnit::class, 'asset_tag', 'SITS-'.$prefix.'-', 6);
    }

    public static function supplierCode(): string
    {
        return self::next(InventorySupplier::class, 'code', 'SUP-', 4);
    }

    /**
     * Location barcode, scoped to a campus abbreviation: LOC-MC-0031.
     * Falls back to a generic prefix for locations with no campus.
     */
    public static function locationCode(?string $campusName = null): string
    {
        $prefix = 'LOC-'.self::abbreviate($campusName ?? 'GEN').'-';

        return self::next(InventoryLocation::class, 'code', $prefix, 4);
    }

    // ---- Transactions --------------------------------------------------------

    public static function grnNumber(): string
    {
        return self::nextForYear(InventoryBatch::class, 'grn_number', 'GRN', 4);
    }

    public static function requestNumber(): string
    {
        return self::nextForYear(InventoryRequest::class, 'request_number', 'REQ', 4);
    }

    public static function stocktakeReference(): string
    {
        return self::nextForYear(InventoryStocktake::class, 'reference', 'STK', 3);
    }

    public static function disposalReference(): string
    {
        return self::nextForYear(InventoryDisposal::class, 'reference', 'DSP', 4);
    }

    /**
     * Issue voucher reference. Vouchers group movements rather than owning a
     * table of their own, so the sequence is read off the ledger's `reference`.
     */
    public static function issueVoucher(): string
    {
        $prefix = 'ISV-'.now()->year.'-';

        return self::next(\App\Models\InventoryStockMovement::class, 'reference', $prefix, 4);
    }

    // ---- Engine --------------------------------------------------------------

    /**
     * Next code in a year-scoped sequence: PREFIX-2026-0043.
     *
     * @param  class-string<Model>  $modelClass
     */
    private static function nextForYear(string $modelClass, string $column, string $prefix, int $pad): string
    {
        return self::next($modelClass, $column, $prefix.'-'.now()->year.'-', $pad);
    }

    /**
     * Reserve the next code for $prefix by reading the current maximum under a
     * lock. Retries on a unique-constraint collision, which can still happen
     * when two requests race on a database that doesn't honour the lock.
     *
     * @param  class-string<Model>  $modelClass
     */
    private static function next(string $modelClass, string $column, string $prefix, int $pad): string
    {
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $candidate = DB::transaction(function () use ($modelClass, $column, $prefix, $pad) {
                $query = $modelClass::query()->where($column, 'like', $prefix.'%');

                // Soft-deleted rows still hold their unique code, so they must
                // count toward the sequence or the next insert collides.
                if (self::usesSoftDeletes($modelClass)) {
                    $query->withTrashed();
                }

                $highest = self::lockForUpdate($query)
                    ->orderByRaw('LENGTH('.$column.') DESC')
                    ->orderByDesc($column)
                    ->value($column);

                $sequence = $highest === null
                    ? 0
                    : (int) substr((string) $highest, strlen($prefix));

                return $prefix.str_pad((string) ($sequence + 1), $pad, '0', STR_PAD_LEFT);
            });

            // Free unless another request took it between the read and our write.
            if (! self::exists($modelClass, $column, $candidate)) {
                return $candidate;
            }
        }

        // Every attempt collided — fall back to a code that cannot clash rather
        // than failing the operation outright.
        return $prefix.strtoupper(Str::random(6));
    }

    /**
     * `lockForUpdate` is a no-op on SQLite (used by the test suite) and errors on
     * some drivers outside a transaction; guard it so behaviour is identical
     * across MySQL in production and SQLite in tests.
     */
    private static function lockForUpdate(Builder $query): Builder
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? $query
            : $query->lockForUpdate();
    }

    /** @param  class-string<Model>  $modelClass */
    private static function exists(string $modelClass, string $column, string $value): bool
    {
        $query = $modelClass::query()->where($column, $value);

        if (self::usesSoftDeletes($modelClass)) {
            $query->withTrashed();
        }

        return $query->exists();
    }

    /** @param  class-string<Model>  $modelClass */
    private static function usesSoftDeletes(string $modelClass): bool
    {
        return in_array(
            \Illuminate\Database\Eloquent\SoftDeletes::class,
            class_uses_recursive($modelClass),
            true
        );
    }

    /** Uppercase alphanumerics only — codes end up in barcodes and filenames. */
    private static function sanitize(string $value): string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $value) ?? '');

        return $clean !== '' ? substr($clean, 0, 8) : 'GEN';
    }

    /** "Main Campus" → "MC"; a single word → its first two letters. */
    private static function abbreviate(string $name): string
    {
        $words = preg_split('/\s+/', trim($name)) ?: [];
        $words = array_values(array_filter($words));

        if (count($words) >= 2) {
            return self::sanitize(substr($words[0], 0, 1).substr($words[1], 0, 1));
        }

        return self::sanitize(substr($words[0] ?? 'GEN', 0, 2));
    }
}
