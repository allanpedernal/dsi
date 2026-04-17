<?php

namespace App\Notifications;

use App\Models\Sale;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewSaleNotification extends Notification implements ShouldBroadcastNow
{
    public function __construct(public Sale $sale) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'sale_id' => $this->sale->id,
            'reference' => $this->sale->reference,
            'total' => (float) $this->sale->total,
            'customer' => $this->sale->customer?->full_name,
            'message' => "New sale {$this->sale->reference} (\$".number_format((float) $this->sale->total, 2).')',
            'created_at' => $this->sale->created_at?->toIso8601String(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage($this->toArray($notifiable)))
            ->onConnection('sync');
    }
}
