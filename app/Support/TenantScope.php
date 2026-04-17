<?php

namespace App\Support;

use App\Models\User;

/**
 * Resolves which customer_ids bound a query for multi-tenant data.
 *
 * Rules:
 * - Tenant-scoped users (customer role WITH at least one customer in their pivot)
 *   can only see rows whose customer_id is in that pivot set. They cannot escape scope
 *   by passing a different filter value.
 * - Everyone else (admin / manager / cashier) defaults to "all customers" and may
 *   narrow via an optional $requestedCustomerId filter.
 *
 * Consumers should call both {@see forUser()} (single pick convenience) and
 * {@see idsForUser()} (full pivot set) depending on how they want to build the WHERE.
 */
class TenantScope
{
    /**
     * Returns a list of customer_ids the user's queries must be confined to,
     * or null if the user may see any customer's data (admin/manager/cashier).
     *
     * @return array<int, int>|null
     */
    public static function idsForUser(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        if ($user->isTenantScoped()) {
            return $user->customerIds();
        }

        return null;
    }

    /**
     * Returns a single customer_id to filter by — honouring a filter request when
     * the user isn't tenant-scoped, or collapsing the tenant pivot to its first id
     * when the user is. Returns null when the query should span every customer.
     */
    public static function forUser(?User $user, int|string|null $requestedCustomerId = null): ?int
    {
        $pivot = self::idsForUser($user);

        if ($pivot !== null) {
            // Tenant-scoped: ignore any requested filter, always use (first of) pivot ids.
            // Multi-customer tenant users are rare here; a single id keeps existing
            // ->where('customer_id', …) code paths working.
            return $pivot[0] ?? null;
        }

        if ($requestedCustomerId === null || $requestedCustomerId === '' || $requestedCustomerId === '0') {
            return null;
        }

        $id = (int) $requestedCustomerId;

        return $id > 0 ? $id : null;
    }
}
