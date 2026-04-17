<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('redirects a forbidden page to the first accessible page', function () {
    $user = User::factory()->create();
    // Give only sales.view — no dashboard.view, so /dashboard should redirect to /sales.
    $user->givePermissionTo('sales.view');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect('/sales');
});

it('redirects a permission-less user to the welcome home page', function () {
    // No role assigned → no permissions at all.
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect('/home');
});

it('renders the welcome home page for users with no permissions', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/home')
        ->assertOk();
});

it('redirects /home to the first accessible page when the user has access somewhere', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('sales.view');

    $this->actingAs($user)
        ->get('/home')
        ->assertRedirect('/sales');
});
