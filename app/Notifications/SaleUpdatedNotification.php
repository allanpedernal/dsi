<?php

namespace App\Notifications;

use App\Models\Sale;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies admin / manager users that a sale has been updated.
 */
class SaleUpdatedNotification extends Notification implements ShouldBroadcastNow
{
    public function __construct(public Sale $sale) {}

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
            'action' => 'updated',
            'sale_id' => $this->sale->id,
            'reference' => $this->sale->reference,
            'total' => (float) $this->sale->total,
            'customer' => $this->sale->customer?->full_name,
            'message' => "Sale {$this->sale->reference} updated (\$".number_format((float) $this->sale->total, 2).')',
            'updated_at' => $this->sale->updated_at?->toIso8601String(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
