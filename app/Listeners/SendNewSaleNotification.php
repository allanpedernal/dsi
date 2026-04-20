<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\SaleCreated;
use App\Models\User;
use App\Notifications\NewSaleNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Notifies admin and manager users when a new sale has been recorded.
 */
class SendNewSaleNotification
{
    public function handle(SaleCreated $event): void
    {
        $recipients = User::role([UserRole::Admin->value, UserRole::Manager->value])->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new NewSaleNotification($event->sale));
    }
}
