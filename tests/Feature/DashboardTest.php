<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('users with dashboard.view permission can visit the dashboard', function () {
    $this->seed(RolePermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole(UserRole::Manager->value);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('users without dashboard.view permission are redirected to an accessible page', function () {
    $this->seed(RolePermissionSeeder::class);
    // A user with no role at all → no permissions, no accessible pages → /home.
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect('/home');
});
