<?php

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole(UserRole::Admin->value);
});

it('records source=web when a customer is created via the web app', function () {
    $payload = [
        'first_name' => 'Web',
        'last_name' => 'User',
        'email' => 'web.user@example.com',
    ];

    $this->actingAs($this->admin)->post('/customers', $payload)->assertCreated();

    $activity = Activity::query()->where('log_name', 'customer')->latest('id')->first();
    expect($activity)->not->toBeNull()
        ->and($activity->source)->toBe('web')
        ->and($activity->description)->toContain('created customer Web User');
});

it('records source=api when a customer is created via the api', function () {
    $token = $this->admin->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('Accept', 'application/json')
        ->postJson('/api/v1/customers', [
            'first_name' => 'Api',
            'last_name' => 'Caller',
            'email' => 'api.caller@example.com',
        ])->assertCreated();

    $activity = Activity::query()->where('log_name', 'customer')->latest('id')->first();
    expect($activity)->not->toBeNull()
        ->and($activity->source)->toBe('api')
        ->and($activity->description)->toContain('created customer Api Caller');
});

it('updates customer stock and writes a human-readable description', function () {
    $customer = Customer::factory()->create();

    $this->actingAs($this->admin)
        ->put('/customers/'.$customer->id, [
            'first_name' => 'Renamed',
            'last_name' => 'Person',
            'email' => $customer->email,
        ])->assertOk();

    $activity = Activity::query()->where('log_name', 'customer')->where('event', 'updated')->latest('id')->first();
    expect($activity->description)->toContain('updated customer Renamed Person')
        ->and($activity->source)->toBe('web');
});
