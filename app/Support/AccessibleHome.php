<?php

namespace App\Support;

use App\Models\User;

/**
 * Finds the first page a user is allowed to visit, so we never trap them
 * on a 403 and never hand them a login-landing they can't open.
 */
class AccessibleHome
{
    /** @var array<int, array{permission: string, path: string}> */
    private const MAP = [
        ['permission' => 'dashboard.view', 'path' => '/dashboard'],
        ['permission' => 'sales.view', 'path' => '/sales'],
        ['permission' => 'customers.view', 'path' => '/customers'],
        ['permission' => 'products.view', 'path' => '/products'],
        ['permission' => 'reports.view', 'path' => '/reports/sales'],
        ['permission' => 'audit.view', 'path' => '/audit-log'],
        ['permission' => 'users.view', 'path' => '/users'],
        ['permission' => 'roles.view', 'path' => '/roles'],
        ['permission' => 'permissions.view', 'path' => '/permissions'],
    ];

    public static function for(?User $user): string
    {
        if (! $user) {
            return '/';
        }

        foreach (self::MAP as $entry) {
            if ($user->can($entry['permission'])) {
                return $entry['path'];
            }
        }

        return '/home';
    }
}
