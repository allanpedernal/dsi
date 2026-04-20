<?php

namespace App\Listeners;

use App\Events\SaleCreated;
use App\Events\SaleDeleted;
use App\Events\SalesListChanged;
use App\Events\SaleUpdated;

/**
 * Collapses Sale{Created,Updated,Deleted} into a single SalesListChanged broadcast.
 */
class BroadcastSalesListChanged
{
    public function handle(SaleCreated|SaleUpdated|SaleDeleted $event): void
    {
        [$action, $id, $reference] = match (true) {
            $event instanceof SaleCreated => ['created', $event->sale->id, $event->sale->reference],
            $event instanceof SaleUpdated => ['updated', $event->sale->id, $event->sale->reference],
            $event instanceof SaleDeleted => ['deleted', $event->saleId, $event->reference],
        };

        event(new SalesListChanged($action, $id, $reference));
    }
}
