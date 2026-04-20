<?php

namespace App\Notifications;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies admin / manager users that a sale has been deleted.
 */
class SaleDeletedNotification extends Notification implements ShouldBroadcastNow
{
    public function __construct(public int $saleId, public string $reference) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Persisted notification payload; also used as the broadcast body.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'action' => 'deleted',
            'sale_id' => $this->saleId,
            'reference' => $this->reference,
            'message' => "Sale {$this->reference} deleted",
            'deleted_at' => now()->toIso8601String(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
