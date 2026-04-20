<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Support\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Builds sales report queries and aggregates for the reporting UI and exports.
 */
class ReportService
{
    /**
     * Base sales query honouring tenant scoping and the sales-report filter set.
     *
     * @param  array{from?:?string, to?:?string, status?:?string, user_id?:?int, customer_id?:?int}  $filters
     * @return Builder<Sale>
     */
    public function salesQuery(array $filters): Builder
    {
        $customerId = TenantScope::forUser(Auth::user(), $filters['customer_id'] ?? null);

        return Sale::query()
            ->with(['customer:id,first_name,last_name,code'])
            ->when($customerId !== null, fn (Builder $q) => $q->where('customer_id', $customerId))
            ->when($filters['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($filters['status'] ?? null, fn (Builder $q, $s) => $q->where('status', $s))
            ->when($filters['user_id'] ?? null, fn (Builder $q, $id) => $q->where('user_id', $id))
            ->orderByDesc('created_at');
    }

    /**
     * Aggregate totals for paid sales matching the filter set.
     *
     * @param  array<string, mixed>  $filters
     * @return array{count:int, subtotal:float, tax:float, discount:float, total:float}
     */
    public function salesAggregate(array $filters): array
    {
        $row = $this->salesQuery($filters)
            ->where('status', SaleStatus::Paid->value)
            ->reorder()
            ->selectRaw('COUNT(*) as c, COALESCE(SUM(subtotal),0) as s, COALESCE(SUM(tax),0) as t, COALESCE(SUM(discount),0) as d, COALESCE(SUM(total),0) as g')
            ->first();

        return [
            'count' => (int) ($row->c ?? 0),
            'subtotal' => (float) ($row->s ?? 0),
            'tax' => (float) ($row->t ?? 0),
            'discount' => (float) ($row->d ?? 0),
            'total' => (float) ($row->g ?? 0),
        ];
    }
}
