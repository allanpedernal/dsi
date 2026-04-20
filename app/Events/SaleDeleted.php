<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after a sale is soft-deleted; drives notifications and sales-list refresh.
 */
class SaleDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $saleId, public string $reference) {}
}
