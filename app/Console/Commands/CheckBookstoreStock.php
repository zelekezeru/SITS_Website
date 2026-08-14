<?php

namespace App\Console\Commands;

use App\Enums\Permission;
use App\Models\BookTitle;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\BookstoreLowStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Daily low-stock sweep.
 *
 * Only titles that have crossed their reorder level SINCE THE LAST RUN are
 * reported, so a long-standing shortage does not bury the new one. The set of
 * already-reported titles is kept in settings rather than a table — it is a
 * single scalar that only this command reads.
 */
class CheckBookstoreStock extends Command
{
    protected $signature = 'bookstore:check-stock {--force : Report every low title, not only new ones}';

    protected $description = 'Notify bookstore staff about printed titles at or below their reorder level';

    private const SETTING_KEY = 'bookstore.low_stock.reported_title_ids';

    public function handle(): int
    {
        $low = BookTitle::active()
            ->lowStock()
            ->with('stocks')
            ->orderBy('title')
            ->get()
            ->map(fn (BookTitle $title) => [
                'id'            => $title->id,
                'code'          => $title->code,
                'title'         => $title->title,
                'on_hand'       => $title->total_on_hand,
                'reorder_level' => $title->reorder_level,
            ]);

        $previous = $this->previouslyReported();
        $new      = $this->option('force')
            ? $low
            : $low->reject(fn (array $row) => in_array($row['id'], $previous, true))->values();

        // Remember the current set either way, so a title that recovers and dips
        // again is reported afresh.
        $this->remember($low->pluck('id')->all());

        if ($new->isEmpty()) {
            $this->info('No new low-stock titles.');

            return self::SUCCESS;
        }

        $recipients = User::permission(Permission::MANAGE_BOOK_STOCK->value)->get();

        if ($recipients->isEmpty()) {
            $this->warn('Low stock found but nobody holds the manage_book_stock permission.');

            return self::SUCCESS;
        }

        Notification::send($recipients, new BookstoreLowStock($new));

        $this->info("Notified {$recipients->count()} user(s) about {$new->count()} title(s).");

        return self::SUCCESS;
    }

    /** @return array<int, int> */
    protected function previouslyReported(): array
    {
        $raw = (string) Setting::get(self::SETTING_KEY, '');

        return array_map('intval', array_filter(explode(',', $raw)));
    }

    /** @param  array<int, int>  $ids */
    protected function remember(array $ids): void
    {
        Setting::set(self::SETTING_KEY, implode(',', $ids), 'bookstore', 'string');
    }
}
