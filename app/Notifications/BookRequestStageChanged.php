<?php

namespace App\Notifications;

use App\Enums\BookRequestEvent;
use App\Models\BookRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * One notification class for the whole request journey — the wording lives on
 * {@see BookRequestEvent}, so a new hand-off means a new enum case, not a new
 * class to keep in step with the others.
 */
class BookRequestStageChanged extends Notification
{
    use Queueable;

    public function __construct(
        public readonly BookRequest $bookRequest,
        public readonly BookRequestEvent $event,
        public readonly ?string $note = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->bookRequest;

        $mail = (new MailMessage)
            ->subject("[{$request->request_number}] ".$this->event->subject())
            ->line($this->event->subject().'.')
            ->line($this->event->callToAction())
            ->line("Destination: {$request->destination_name}")
            ->line("Books: {$request->total_quantity} · Value: ".number_format((float) $request->total_amount, 2));

        if ($this->note) {
            $mail->line('Note: '.$this->note);
        }

        return $mail->action('Open request '.$request->request_number, $this->url());
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'bookstore_request',
            'event'          => $this->event->value,
            'title'          => $this->event->subject(),
            'message'        => $this->event->callToAction(),
            'color'          => $this->event->badgeColor(),
            'request_id'     => $this->bookRequest->id,
            'request_number' => $this->bookRequest->request_number,
            'destination'    => $this->bookRequest->destination_name,
            'note'           => $this->note,
            'url'            => $this->url(),
        ];
    }

    protected function url(): string
    {
        return route('bookstore.requests.show', $this->bookRequest->id);
    }
}
