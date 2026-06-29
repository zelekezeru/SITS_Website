<?php

namespace App\Http\Controllers;

use App\Enums\ClosedDayType;
use App\Models\ClosedDay;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Manages the holiday / closed-day registry.
 *
 * Closed days are attached to Mass Permission requests to auto-excused
 * absences for all active employees. Access requires `manage closed days`.
 */
class ClosedDayController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Admin/Finance/ClosedDays/Index', self::pageProps($request->user()));
    }

    /** Shared payload (also served by the ModuleController for the admin nav). */
    public static function pageProps($user): array
    {
        return [
            'closedDays' => ClosedDay::with('createdBy:id,name')
                ->orderBy('date', 'desc')
                ->get()
                ->map(fn ($d) => [
                    'id'          => $d->id,
                    'date'        => $d->date->toDateString(),
                    'name'        => $d->name,
                    'type'        => $d->type->value,
                    'type_label'  => $d->type->label(),
                    'description' => $d->description,
                    'is_active'   => $d->is_active,
                    'created_by'  => $d->createdBy?->name,
                ]),
            'types' => ClosedDayType::options(),
            'can'   => [
                'manage' => (bool) $user?->can('manage closed days'),
            ],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date'        => ['required', 'date', 'unique:closed_days,date'],
            'name'        => ['required', 'string', 'max:200'],
            'type'        => ['required', 'in:'.implode(',', array_column(ClosedDayType::cases(), 'value'))],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        ClosedDay::create(array_merge($data, [
            'is_active'  => true,
            'created_by' => $request->user()->id,
        ]));

        return back()->with('success', 'Closed day added to the calendar.');
    }

    public function update(Request $request, ClosedDay $closedDay)
    {
        $data = $request->validate([
            'date'        => ['required', 'date', 'unique:closed_days,date,'.$closedDay->id],
            'name'        => ['required', 'string', 'max:200'],
            'type'        => ['required', 'in:'.implode(',', array_column(ClosedDayType::cases(), 'value'))],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active'   => ['boolean'],
        ]);

        $closedDay->update($data);

        return back()->with('success', 'Closed day updated.');
    }

    public function destroy(ClosedDay $closedDay)
    {
        $closedDay->delete();

        return back()->with('success', 'Closed day removed.');
    }
}
