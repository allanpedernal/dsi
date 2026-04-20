<?php

namespace App\Events;

use App\Models\Sale;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after a sale is updated; drives notifications and sales-list refresh.
 */
class SaleUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Sale $sale) {}
}
