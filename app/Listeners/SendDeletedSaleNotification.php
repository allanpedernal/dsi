<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\SaleDeleted;
use App\Models\User;
use App\Notifications\SaleDeletedNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Notifies admin and manager users when a sale has been deleted.
 */
class SendDeletedSaleNotification
{
    public function handle(SaleDeleted $event): void
    {
        $recipients = User::role([UserRole::Admin->value, UserRole::Manager->value])->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new SaleDeletedNotification($event->saleId, $event->reference));
    }
}
