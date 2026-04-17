<?php

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
    $this->seed(RolePermissionSeeder::class);
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register and get a customer record + pivot link', function () {
    $response = $this->post(route('register.store'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane.doe@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/dashboard');

    $user = User::where('email', 'jane.doe@example.com')->firstOrFail();
    expect($user->name)->toBe('Jane Doe')
        ->and($user->hasRole(UserRole::Customer->value))->toBeTrue();

    $customer = Customer::where('email', 'jane.doe@example.com')->firstOrFail();
    expect($customer->first_name)->toBe('Jane')
        ->and($customer->last_name)->toBe('Doe');

    expect($user->customers()->pluck('customers.id')->all())->toContain($customer->id);
});
