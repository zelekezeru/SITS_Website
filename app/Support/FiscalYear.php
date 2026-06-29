<?php

namespace App\Support;

use App\Models\Year;
use Illuminate\Support\Facades\Session;

/**
 * Fiscal-year context for the whole portal.
 *
 * There are two distinct ideas:
 *   - the ACTIVE year   — the institution's current fiscal year (years.active),
 *                          set by the President; the default everyone sees.
 *   - the VIEWING year  — the year currently being browsed. Defaults to the
 *                          active year, but the user may choose to view an older
 *                          year (kept in the session) to fetch historical data.
 *
 * Module queries should scope to FiscalYear::current() so that switching the
 * viewing year transparently swaps the data set, while leaving the institution's
 * active year unchanged until it is explicitly re-activated.
 */
class FiscalYear
{
    private const SESSION_KEY = 'fiscal_year.viewing_id';

    /** The institution's active fiscal year. */
    public static function active(): ?Year
    {
        return Year::where('active', true)->first();
    }

    /** The year currently being viewed (session choice → active → most recent). */
    public static function current(): ?Year
    {
        $viewingId = Session::get(self::SESSION_KEY);

        if ($viewingId && $year = Year::find($viewingId)) {
            return $year;
        }

        return self::active() ?? Year::orderByDesc('start_date')->first();
    }

    public static function currentId(): ?int
    {
        return self::current()?->id;
    }

    /** True when the user is browsing a year other than the active one. */
    public static function isHistorical(): bool
    {
        $current = self::current();
        $active = self::active();

        return $current && $active && $current->id !== $active->id;
    }

    /** Persist the viewing-year choice. Null clears it (back to active). */
    public static function setViewing(?int $yearId): void
    {
        if ($yearId === null) {
            Session::forget(self::SESSION_KEY);

            return;
        }

        Session::put(self::SESSION_KEY, $yearId);
    }

    /**
     * Payload shared to the front-end for the year switcher.
     *
     * @return array<string, mixed>
     */
    public static function payload(): array
    {
        $current = self::current();
        $active = self::active();

        return [
            'years' => Year::orderByDesc('start_date')->get(['id', 'label', 'active', 'start_date', 'end_date']),
            'currentId' => $current?->id,
            'activeId' => $active?->id,
            'currentLabel' => $current?->label,
            'isHistorical' => self::isHistorical(),
        ];
    }
}
