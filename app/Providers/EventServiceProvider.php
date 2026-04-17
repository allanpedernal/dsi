<?php

namespace App\Providers;

use App\Events\SaleCreated;
use App\Listeners\SendNewSaleNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /** @var array<class-string, array<int, class-string>> */
    protected $listen = [
        SaleCreated::class => [
            SendNewSaleNotification::class,
        ],
    ];
}
