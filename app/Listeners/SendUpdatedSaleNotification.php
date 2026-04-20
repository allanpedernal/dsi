<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\SaleUpdated;
use App\Models\User;
use App\Notifications\SaleUpdatedNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Notifies admin and manager users when a sale has been updated.
 */
class SendUpdatedSaleNotification
{
    public function handle(SaleUpdated $event): void
    {
        $recipients = User::role([UserRole::Admin->value, UserRole::Manager->value])->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new SaleUpdatedNotification($event->sale));
    }
}
