<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Support\TenantScope;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $customerId = TenantScope::forUser($user, $request->query('customer_id'));

        $cacheKey = sprintf('dashboard:metrics:%d:%s', $user->id, $customerId ?? 'all');
        $metrics = Cache::remember($cacheKey, 60, fn () => $this->buildMetrics($customerId));

        $props = array_merge($metrics, [
            'filter' => [
                'customer_id' => $customerId,
                'locked' => $user->isTenantScoped(),
            ],
            'customers' => $user->isTenantScoped()
                ? []
                : Customer::orderBy('last_name')->orderBy('first_name')->get(['id', 'code', 'first_name', 'last_name'])
                    ->map(fn ($c) => ['id' => $c->id, 'label' => trim("{$c->first_name} {$c->last_name}").' ('.$c->code.')'])
                    ->values()
                    ->all(),
        ]);

        return Inertia::render('dashboard', $props);
    }

    /** @return array<string, mixed> */
    private function buildMetrics(?int $customerId): array
    {
        $today = Carbon::today();
        $thirtyDaysAgo = Carbon::today()->subDays(29);
        $sixtyDaysAgo = Carbon::today()->subDays(59);

        $scopeSales = fn ($q) => $customerId !== null ? $q->where('customer_id', $customerId) : $q;

        $todayStats = Sale::query()
            ->where('status', SaleStatus::Paid->value)
            ->whereDate('created_at', $today)
            ->tap($scopeSales)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total),0) as revenue, COALESCE(AVG(total),0) as aov')
            ->first();

        $monthRevenue = Sale::query()
            ->where('status', SaleStatus::Paid->value)
            ->whereDate('created_at', '>=', $thirtyDaysAgo)
            ->tap($scopeSales)
            ->sum('total');

        // Previous 30-day window for % comparison.
        $prevMonthRevenue = Sale::query()
            ->where('status', SaleStatus::Paid->value)
            ->whereBetween('created_at', [$sixtyDaysAgo, $thirtyDaysAgo->copy()->subSecond()])
            ->tap($scopeSales)
            ->sum('total');

        $monthOrders = Sale::query()
            ->where('status', SaleStatus::Paid->value)
            ->whereDate('created_at', '>=', $thirtyDaysAgo)
            ->tap($scopeSales)
            ->count();

        $avgOrderValue = $monthOrders > 0 ? (float) $monthRevenue / $monthOrders : 0.0;

        $trendRows = Sale::query()
            ->where('status', SaleStatus::Paid->value)
            ->whereDate('created_at', '>=', $thirtyDaysAgo)
            ->tap($scopeSales)
            ->selectRaw('DATE(created_at) as d, COALESCE(SUM(total),0) as revenue, COUNT(*) as orders')
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        $trend = [];
        for ($i = 0; $i < 30; $i++) {
            $date = $thirtyDaysAgo->copy()->addDays($i)->toDateString();
            $row = $trendRows->get($date);
            $trend[] = [
                'date' => $date,
                'revenue' => (float) ($row->revenue ?? 0),
                'orders' => (int) ($row->orders ?? 0),
            ];
        }

        $topProducts = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', SaleStatus::Paid->value)
            ->whereDate('sales.created_at', '>=', $thirtyDaysAgo)
            ->when($customerId !== null, fn ($q) => $q->where('sales.customer_id', $customerId))
            ->selectRaw('sale_items.product_name, sale_items.product_sku, SUM(sale_items.quantity) as units, SUM(sale_items.line_total) as revenue')
            ->groupBy('sale_items.product_name', 'sale_items.product_sku')
            ->orderByDesc('units')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'name' => $r->product_name,
                'sku' => $r->product_sku,
                'units' => (int) $r->units,
                'revenue' => (float) $r->revenue,
            ]);

        $lowStock = Product::query()
            ->when($customerId !== null, fn ($q) => $q->where('customer_id', $customerId))
            ->whereColumn('stock', '<=', 'reorder_level')
            ->orderBy('stock')
            ->limit(8)
            ->get(['id', 'name', 'sku', 'stock', 'reorder_level'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'stock' => $p->stock,
                'reorder_level' => $p->reorder_level,
            ]);

        $recentActivity = Activity::query()
            ->with('causer:id,name')
            ->when($customerId !== null, fn ($q) => $q->where('customer_id', $customerId))
            ->latest()
            ->limit(10)
            ->get(['id', 'log_name', 'description', 'source', 'causer_id', 'causer_type', 'created_at'])
            ->map(fn ($a) => [
                'id' => $a->id,
                'description' => $a->description,
                'source' => $a->source,
                'causer' => $a->causer?->name,
                'created_at' => $a->created_at?->diffForHumans(),
            ]);

        return [
            'today' => [
                'count' => (int) ($todayStats->count ?? 0),
                'revenue' => (float) ($todayStats->revenue ?? 0),
                'aov' => (float) ($todayStats->aov ?? 0),
            ],
            'month_revenue' => (float) $monthRevenue,
            'prev_month_revenue' => (float) $prevMonthRevenue,
            'month_orders' => (int) $monthOrders,
            'avg_order_value' => (float) $avgOrderValue,
            'trend' => $trend,
            'top_products' => $topProducts->values()->all(),
            'low_stock' => $lowStock->values()->all(),
            'recent_activity' => $recentActivity->values()->all(),
        ];
    }
}
