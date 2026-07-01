<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Enums\BookStatus;
use App\Models\BookCopy;
use App\Models\ShelfBox;
use App\Models\PlacementLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScanController extends Controller
{
    public function resolve(Request $request)
    {
        $hash = $request->validate(['hash' => 'required|string'])['hash'];

        if ($box = ShelfBox::with('row.floor.campus')->where('tracking_hash', $hash)->first()) {
            return response()->json([
                'type' => 'shelf_box',
                'id'   => $box->id,
                'label'=> $box->label,
                'path' => "{$box->row->floor->campus->name} › {$box->row->floor->name} › Row {$box->row->label} › {$box->label}",
                'campus_id' => $box->row->floor->campus_id,
            ]);
        }

        if ($copy = BookCopy::with('book','shelfBox.row.floor.campus')->where('tracking_hash', $hash)->first()) {
            return response()->json([
                'type'  => 'book_copy',
                'id'    => $copy->id,
                'title' => $copy->book->title,
                'status'=> $copy->status,
                'currently_at' => $copy->shelfBox
                    ? "{$copy->shelfBox->row->floor->campus->name} › {$copy->shelfBox->label}"
                    : null,
            ]);
        }

        return response()->json(['type' => 'unknown'], 404);
    }

    public function place(Request $request)
    {
        $data = $request->validate([
            'shelf_box_hash' => 'required|string',
            'copy_hashes'    => 'required|array|min:1',
            'copy_hashes.*'  => 'string',
            'reason'         => 'in:initial_place,reshelve,correction',
            'note'           => 'nullable|string|max:500',
        ]);

        $box = ShelfBox::with('row.floor')->where('tracking_hash', $data['shelf_box_hash'])->firstOrFail();

        // Enforce Campus Admin bounds or user rules? For now, we'll allow but we could block.

        DB::transaction(function () use ($data, $box, $request) {
            $copies = BookCopy::with('book')->whereIn('tracking_hash', $data['copy_hashes'])->lockForUpdate()->get();

            foreach ($copies as $copy) {
                if (in_array($copy->status, [BookStatus::CHECKED_OUT, BookStatus::IN_TRANSIT, BookStatus::PENDING_TRANSFER])) {
                    throw ValidationException::withMessages([
                        'copy_hashes' => "{$copy->book->title} is currently {$copy->status->value} — resolve that first.",
                    ]);
                }

                PlacementLog::create([
                    'book_copy_id'      => $copy->id,
                    'from_shelf_box_id' => $copy->current_shelf_box_id,
                    'to_shelf_box_id'   => $box->id,
                    'user_id'           => $request->user()->id,
                    'reason'            => $data['reason'] ?? 'reshelve',
                    'note'              => $data['note'] ?? null,
                ]);

                $copy->update([
                    'current_shelf_box_id' => $box->id,
                    'status' => $copy->status === BookStatus::LOST ? BookStatus::AVAILABLE : $copy->status,
                ]);
            }
        });

        return response()->json(['ok' => true, 'count' => count($data['copy_hashes'])]);
    }

    public function bulkPlace(Request $request)
    {
        // Stub for later bulk CSV import matching Phase 5 user suggestion
        return response()->json(['message' => 'Not implemented yet'], 501);
    }
}
