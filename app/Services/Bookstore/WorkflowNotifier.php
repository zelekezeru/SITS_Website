<?php

namespace App\Services\Bookstore;

use App\Enums\BookRequestEvent;
use App\Models\BookRequest;
use App\Models\User;
use App\Notifications\BookRequestStageChanged;
use Illuminate\Support\Facades\Notification;

/**
 * Tells the next person in the chain that it is their turn.
 *
 * Recipients are resolved from the permission the stage requires — not from a
 * role name and not from a configured list — so granting somebody
 * `verify_book_payment` automatically starts their notifications, and revoking
 * it stops them. There is no second place to keep in step.
 */
class WorkflowNotifier
{
    /**
     * @param  User|null  $actor  the person who caused the event; never notified
     *                            about their own action
     */
    public function fire(BookRequest $request, BookRequestEvent $event, ?User $actor = null, ?string $note = null): void
    {
        $recipients = $this->recipientsFor($request, $event);

        if ($actor) {
            $recipients = $recipients->reject(fn (User $user) => $user->is($actor));
        }

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new BookRequestStageChanged($request, $event, $note));
    }

    /**
     * Everyone who should hear about this event: holders of the permission that
     * owns the next stage, plus the requester where the event concerns them.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function recipientsFor(BookRequest $request, BookRequestEvent $event)
    {
        $users = collect();

        foreach ($event->notifyPermissions() as $permission) {
            $users = $users->merge(User::permission($permission->value)->get());
        }

        if ($event->notifiesRequester() && $request->requester) {
            $users->push($request->requester);
        }

        return $users->unique('id')->values();
    }

    /**
     * Who currently owes this request an action — used by the pipeline screen so
     * a stakeholder can see the name to chase, not just the stage.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function currentOwners(BookRequest $request)
    {
        $permission = $request->status->awaitingPermission();

        if (! $permission) {
            return collect();
        }

        return User::permission($permission->value)->get(['id', 'name', 'email']);
    }
}
