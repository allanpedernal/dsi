<?php

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->customerA = Customer::factory()->create();
    $this->customerB = Customer::factory()->create();

    $this->productA = Product::factory()->create(['customer_id' => $this->customerA->id]);
    $this->productB = Product::factory()->create(['customer_id' => $this->customerB->id]);

    $manager = User::factory()->create();
    $manager->assignRole(UserRole::Manager->value);
    $this->admin = User::factory()->create();
    $this->admin->assignRole(UserRole::Admin->value);

    Sale::factory()->create(['customer_id' => $this->customerA->id, 'user_id' => $manager->id]);
    Sale::factory()->create(['customer_id' => $this->customerA->id, 'user_id' => $manager->id]);
    Sale::factory()->create(['customer_id' => $this->customerB->id, 'user_id' => $manager->id]);
});

function tenantUser(int $customerId): User
{
    $user = User::factory()->create();
    $user->assignRole(UserRole::Customer->value);
    $user->customers()->attach($customerId);

    return $user;
}

it('scopes a tenant-scoped customer user to their own products only', function () {
    $user = tenantUser($this->customerA->id);

    $response = $this->actingAs($user)->getJson('/products/data');
    $response->assertOk();

    $rows = $response->json('data');
    expect($rows)->toHaveCount(1)
        ->and($rows[0]['id'])->toBe($this->productA->id);
});

it('scopes a tenant-scoped customer user to their own sales only', function () {
    $user = tenantUser($this->customerA->id);

    $response = $this->actingAs($user)->getJson('/sales/data');
    $response->assertOk();

    expect($response->json('data'))->toHaveCount(2);
});

it('ignores customer_id override from tenant-scoped user', function () {
    $user = tenantUser($this->customerA->id);

    $response = $this->actingAs($user)->getJson("/sales/data?customer_id={$this->customerB->id}");
    $response->assertOk();

    // Still only customer A's sales — the URL filter cannot escape tenant scope.
    expect($response->json('data'))->toHaveCount(2);
});

it('lets admin see all sales unfiltered', function () {
    $response = $this->actingAs($this->admin)->getJson('/sales/data');
    $response->assertOk();

    expect($response->json('data'))->toHaveCount(3);
});

it('lets admin narrow by customer_id filter', function () {
    $response = $this->actingAs($this->admin)->getJson("/sales/data?customer_id={$this->customerB->id}");
    $response->assertOk();

    expect($response->json('data'))->toHaveCount(1);
});
