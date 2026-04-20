<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('sales.admin', function (User $user) {
    return $user->hasAnyRole([UserRole::Admin->value, UserRole::Manager->value]);
});
