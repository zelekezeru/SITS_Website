<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Titles that crossed their reorder level since the last check.
 *
 * Deliberately threshold-CROSSING rather than "still low": a title that has been
 * below its level for a month should not generate a daily email, or the alert
 * stops being read.
 */
class BookstoreLowStock extends Notification
{
    use Queueable;

    /** @param  Collection<int, array{code: string, title: string, on_hand: int, reorder_level: int}>  $titles */
    public function __construct(public readonly Collection $titles)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Bookstore: '.$this->titles->count().' title(s) hit the reorder level')
            ->line('These printed titles have dropped to or below their reorder level:');

        foreach ($this->titles as $title) {
            $mail->line("• {$title['code']} — {$title['title']}: {$title['on_hand']} on hand (reorder at {$title['reorder_level']})");
        }

        return $mail
            ->action('Review low stock', route('bookstore.stock.low'))
            ->line('Raise a print run for anything you expect to issue this term.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type'   => 'bookstore_low_stock',
            'count'  => $this->titles->count(),
            'titles' => $this->titles->all(),
            'url'    => route('bookstore.stock.low'),
        ];
    }
}
