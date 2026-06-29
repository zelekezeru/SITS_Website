<?php

namespace App\Http\Controllers\Finance;

use App\Enums\PayrollStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $periods = PayrollPeriod::monthly()->forActiveYear()
            ->withCount('payslips')
            ->withSum('payslips as net_total', 'net_pay')
            ->orderByDesc('start_date')
            ->get();

        $current = $periods->first(fn ($p) => ! $p->isApproved()) ?? $periods->first();

        return Inertia::render('Finance/Dashboard', [
            'stats' => [
                'activeEmployees' => Employee::where('is_active', true)->count(),
                'periodsTotal' => $periods->count(),
                'pendingApproval' => $periods->where('status', PayrollStatus::PendingApproval)->count(),
                'rejected' => $periods->where('status', PayrollStatus::Rejected)->count(),
                'currentPeriod' => $current ? [
                    'id' => $current->id,
                    'name' => $current->name,
                    'status' => $current->status->value,
                    'statusLabel' => $current->status->label(),
                    'payslips' => $current->payslips_count,
                    'netTotal' => (float) $current->net_total,
                ] : null,
            ],
            'periods' => $periods->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'status' => $p->status->value,
                'statusLabel' => $p->status->label(),
                'payslips' => $p->payslips_count,
                'netTotal' => (float) $p->net_total,
            ]),
        ]);
    }
}
