<?php

namespace App\Services;

use App\Support\TenantScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

/**
 * Reads and paginates audit activity rows, applying tenant and filter scoping.
 */
class AuditService
{
    /**
     * Paginate activity rows, honouring tenant scoping and filter inputs.
     *
     * @param  array{search?:?string, source?:?string, log_name?:?string, from?:?string, to?:?string, customer_id?:?int, per_page?:?int}  $filters
     * @return LengthAwarePaginator<int, Activity>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 25)));
        $customerId = TenantScope::forUser(Auth::user(), $filters['customer_id'] ?? null);

        return Activity::query()
            ->with('causer:id,name,email')
            ->when($customerId !== null, fn ($q) => $q->where('customer_id', $customerId))
            ->when($filters['search'] ?? null, fn ($q, $t) => $q->where('description', 'like', "%{$t}%"))
            ->when($filters['source'] ?? null, fn ($q, $s) => $q->where('source', $s))
            ->when($filters['log_name'] ?? null, fn ($q, $n) => $q->where('log_name', $n))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate($perPage);
    }
}
