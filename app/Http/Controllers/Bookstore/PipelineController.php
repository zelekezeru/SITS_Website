<?php

namespace App\Http\Controllers\Bookstore;

use App\Enums\BookRequestStage;
use App\Enums\BookRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\BookPaymentBypass;
use App\Models\BookRequest;
use App\Models\BookRequestApproval;
use App\Services\Bookstore\WorkflowNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The shared pipeline board.
 *
 * Open to anyone who can see the bookstore, on purpose: the point of the layered
 * approval is that stakeholders can watch it move. Every open request appears
 * with the stage it is at, who owes the next action, and how long it has been
 * sitting there — so a lag has a name and a number against it, not just a
 * feeling that things are slow.
 */
class PipelineController extends Controller
{
    public function __construct(private readonly WorkflowNotifier $notifier)
    {
    }

    public function index(Request $request): Response
    {
        $requests = BookRequest::open()
            ->with([
                'requester:id,name',
                'center:id,name',
                'campus:id,name,name_en',
                'approvals',
                'paymentBypasses',
            ])
            ->orderBy('updated_at')
            ->get();

        $stages = collect(BookRequestStatus::open())->map(function (BookRequestStatus $status) use ($requests) {
            $inStage = $requests->where('status', $status);

            return [
                'status'      => $status->value,
                'label'       => $status->label(),
                'color'       => $status->badgeColor(),
                'waiting_on'  => $status->awaitingDescription(),
                'count'       => $inStage->count(),
                'value'       => round($inStage->sum(fn (BookRequest $r) => (float) $r->total_amount), 2),
                'oldest_days' => $inStage->max(fn (BookRequest $r) => intdiv($r->current_stage_age ?? 0, 86400)) ?? 0,
            ];
        })->values();

        return Inertia::render('Bookstore/Pipeline/Index', [
            'stages'   => $stages,
            'requests' => $requests->map(fn (BookRequest $r) => $this->summarise($r))->values(),
            'lag'      => $this->averageLagByStage(),
            'alerts'   => [
                'stalled'          => $requests->filter(fn (BookRequest $r) => ($r->current_stage_age ?? 0) > 3 * 86400)->count(),
                'pending_bypasses' => BookPaymentBypass::pending()->count(),
                'overdue_bypasses' => BookPaymentBypass::outstanding()
                    ->whereNotNull('promised_on')
                    ->whereDate('promised_on', '<', now())
                    ->count(),
            ],
            'stalledAfterDays' => 3,
        ]);
    }

    /** @return array<string, mixed> */
    protected function summarise(BookRequest $request): array
    {
        $ageSeconds = $request->current_stage_age ?? 0;

        return [
            'id'              => $request->id,
            'request_number'  => $request->request_number,
            'destination'     => $request->destination_name,
            'requester'       => $request->requester?->name,
            'status'          => $request->status->value,
            'status_label'    => $request->status->label(),
            'color'           => $request->status->badgeColor(),
            'waiting_on'      => $request->status->awaitingDescription(),
            // Names, not just a stage — this is who to go and ask.
            'owners'          => $this->notifier->currentOwners($request)->pluck('name')->take(4)->values(),
            'quantity'        => $request->total_quantity,
            'amount'          => (float) $request->total_amount,
            'stage_age_hours' => round($ageSeconds / 3600, 1),
            'stage_age_days'  => intdiv($ageSeconds, 86400),
            'total_age_days'  => intdiv($request->total_elapsed_seconds, 86400),
            'entered_stage_at' => $request->currentStageEnteredAt()?->toIso8601String(),
            'created_at'      => $request->created_at->toIso8601String(),
            'deferred'        => $request->activeBypass() !== null,
            'bypass_pending'  => $request->pendingBypass() !== null,
        ];
    }

    /**
     * Average and worst dwell time per stage, over completed steps.
     *
     * Reads straight off the frozen `waited_seconds` on the approval trail, so
     * it answers "which layer is the bottleneck" without reconstructing history.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function averageLagByStage(): array
    {
        $rows = BookRequestApproval::query()
            ->whereNotNull('waited_seconds')
            ->groupBy('stage')
            ->get([
                'stage',
                DB::raw('count(*) as samples'),
                DB::raw('avg(waited_seconds) as avg_seconds'),
                DB::raw('max(waited_seconds) as max_seconds'),
            ])
            ->keyBy(fn ($row) => $row->stage instanceof BookRequestStage ? $row->stage->value : (string) $row->stage);

        return collect(BookRequestStage::cases())
            ->map(function (BookRequestStage $stage) use ($rows) {
                $row = $rows->get($stage->value);

                return [
                    'stage'        => $stage->value,
                    'label'        => $stage->label(),
                    'samples'      => (int) ($row->samples ?? 0),
                    'avg_hours'    => $row ? round(((float) $row->avg_seconds) / 3600, 1) : null,
                    'worst_hours'  => $row ? round(((float) $row->max_seconds) / 3600, 1) : null,
                ];
            })
            ->values()
            ->all();
    }
}
