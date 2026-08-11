<?php

namespace App\Models;

use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A printing batch received into the store — the module's only source of new
 * stock. Posting one writes a RECEIPT movement and rolls the title's weighted
 * average unit cost.
 */
class PrintRun extends Model
{
    use HasFactory, LogsOperationalActivity;

    protected $fillable = [
        'book_title_id',
        'batch_number',
        'quantity',
        'unit_cost',
        'total_cost',
        'printer_name',
        'invoice_number',
        'crv_number',
        'printed_on',
        'received_on',
        'received_by',
        'shelf_section_id',
        'notes',
    ];

    protected $casts = [
        'quantity'    => 'integer',
        'unit_cost'   => 'decimal:2',
        'total_cost'  => 'decimal:2',
        'printed_on'  => 'date',
        'received_on' => 'date',
    ];

    public function bookTitle(): BelongsTo
    {
        return $this->belongsTo(BookTitle::class);
    }

    public function shelfSection(): BelongsTo
    {
        return $this->belongsTo(ShelfSection::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    /** Scanned printer invoices, delivery notes, etc. */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
