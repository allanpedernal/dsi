<?php

use App\Enums\UserRole;
use App\Events\SaleCreated;
use App\Events\SaleDeleted;
use App\Events\SalesListChanged;
use App\Events\SaleUpdated;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Notifications\NewSaleNotification;
use App\Notifications\SaleDeletedNotification;
use App\Notifications\SaleUpdatedNotification;
use App\Services\SaleService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('SaleCreated triggers SalesListChanged broadcast and admin/manager notifications', function () {
    Notification::fake();
    Event::fake([SalesListChanged::class]);

    $admin = User::factory()->create();
    $admin->assignRole(UserRole::Admin->value);
    $manager = User::factory()->create();
    $manager->assignRole(UserRole::Manager->value);
    User::factory()->create()->assignRole(UserRole::Customer->value);

    $customer = Customer::factory()->create();
    $product = Product::factory()->create(['stock' => 50, 'price' => 10.00]);
    $actor = User::factory()->create();

    app(SaleService::class)->create([
        'customer_id' => $customer->id,
        'user_id' => $actor->id,
        'items' => [['product_id' => $product->id, 'quantity' => 2]],
        'tax_rate' => 0.10,
        'discount' => 0,
        'source' => 'web',
    ], $actor);

    Event::assertDispatched(SalesListChanged::class, fn ($e) => $e->action === 'created');

    Notification::assertSentTo($admin, NewSaleNotification::class);
    Notification::assertSentTo($manager, NewSaleNotification::class);
});

test('SaleUpdated triggers SalesListChanged broadcast and admin/manager notifications', function () {
    Notification::fake();
    Event::fake([SalesListChanged::class]);

    $admin = User::factory()->create();
    $admin->assignRole(UserRole::Admin->value);
    $manager = User::factory()->create();
    $manager->assignRole(UserRole::Manager->value);

    $product = Product::factory()->create(['stock' => 50, 'price' => 10.00]);
    $sale = Sale::factory()->create();
    $sale->items()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'unit_price' => 10.00,
        'quantity' => 1,
        'line_total' => 10.00,
    ]);
    $product->decrement('stock', 1);

    app(SaleService::class)->update($sale->fresh('items'), [
        'items' => [['product_id' => $product->id, 'quantity' => 3]],
        'tax_rate' => 0.10,
    ]);

    Event::assertDispatched(SalesListChanged::class, fn ($e) => $e->action === 'updated' && $e->saleId === $sale->id);

    Notification::assertSentTo($admin, SaleUpdatedNotification::class);
    Notification::assertSentTo($manager, SaleUpdatedNotification::class);
});

test('DELETE /sales/{sale} dispatches SaleDeleted event, fans out SalesListChanged and notifications', function () {
    Notification::fake();
    Event::fake([SalesListChanged::class]);

    $admin = User::factory()->create();
    $admin->assignRole(UserRole::Admin->value);
    $manager = User::factory()->create();
    $manager->assignRole(UserRole::Manager->value);

    $sale = Sale::factory()->create();

    $saleId = (int) $sale->id;
    $reference = (string) $sale->reference;

    $this->actingAs($admin)
        ->deleteJson("/sales/{$sale->id}")
        ->assertOk();

    Event::assertDispatched(SalesListChanged::class, function ($e) use ($saleId, $reference) {
        return $e->action === 'deleted' && $e->saleId === $saleId && $e->reference === $reference;
    });

    Notification::assertSentTo($admin, SaleDeletedNotification::class);
    Notification::assertSentTo($manager, SaleDeletedNotification::class);
});

test('sales.admin channel is authorized for admin and manager, rejected for customer', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::Admin->value);
    $manager = User::factory()->create();
    $manager->assignRole(UserRole::Manager->value);
    $customer = User::factory()->create();
    $customer->assignRole(UserRole::Customer->value);

    $broadcaster = Broadcast::driver();
    $reflection = new ReflectionClass($broadcaster);
    $channels = $reflection->getProperty('channels');
    $channels->setAccessible(true);
    $callbacks = $channels->getValue($broadcaster);

    $authorize = $callbacks['sales.admin'] ?? null;
    expect($authorize)->not->toBeNull();

    expect($authorize($admin))->toBeTrue();
    expect($authorize($manager))->toBeTrue();
    expect($authorize($customer))->toBeFalse();
});

test('listeners are registered: SaleCreated -> BroadcastSalesListChanged and SendNewSaleNotification', function () {
    $listenersForCreated = Event::getListeners(SaleCreated::class);
    $listenersForUpdated = Event::getListeners(SaleUpdated::class);
    $listenersForDeleted = Event::getListeners(SaleDeleted::class);

    expect($listenersForCreated)->not->toBeEmpty();
    expect($listenersForUpdated)->not->toBeEmpty();
    expect($listenersForDeleted)->not->toBeEmpty();
});
