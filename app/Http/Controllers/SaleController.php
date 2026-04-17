<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Customer;
use App\Models\Sale;
use App\Services\SaleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function __construct(private SaleService $service) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Sale::class);
        $user = $request->user();

        return Inertia::render('sales/index', [
            'statuses' => SaleStatus::options(),
            'tenantScoped' => $user->isTenantScoped(),
            'customers' => $user->isTenantScoped() ? [] : $this->customerOptions(),
        ]);
    }

    /** @return array<int, array{id:int, label:string}> */
    private function customerOptions(): array
    {
        return Customer::orderBy('last_name')->orderBy('first_name')
            ->get(['id', 'code', 'first_name', 'last_name'])
            ->map(fn ($c) => ['id' => $c->id, 'label' => trim("{$c->first_name} {$c->last_name}").' ('.$c->code.')'])
            ->values()
            ->all();
    }

    public function create(): Response
    {
        $this->authorize('create', Sale::class);

        return Inertia::render('sales/create');
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Sale::class);
        $sales = $this->service->paginate(
            $request->only(['search', 'status', 'from', 'to', 'customer_id', 'user_id', 'sort', 'dir', 'per_page'])
        );

        return ApiResponse::ok(SaleResource::collection($sales));
    }

    public function show(Sale $sale): Response|JsonResponse
    {
        $this->authorize('view', $sale);
        $sale->load(['customer', 'cashier', 'items']);

        if (request()->wantsJson()) {
            return ApiResponse::ok(new SaleResource($sale));
        }

        return Inertia::render('sales/show', [
            'sale' => (new SaleResource($sale))->resolve(),
        ]);
    }

    public function store(StoreSaleRequest $request): JsonResponse
    {
        $sale = $this->service->create(
            array_merge($request->validated(), ['user_id' => $request->user()->id, 'source' => 'web']),
            $request->user(),
        );

        return ApiResponse::created(new SaleResource($sale), 'Sale recorded');
    }

    public function destroy(Sale $sale): JsonResponse
    {
        $this->authorize('delete', $sale);
        $sale->delete();

        return ApiResponse::ok(null, 'Sale deleted');
    }

    public function refund(Sale $sale): JsonResponse
    {
        $this->authorize('refund', $sale);
        $sale = $this->service->refund($sale);

        return ApiResponse::ok(new SaleResource($sale->load(['customer', 'cashier', 'items'])), 'Sale refunded');
    }
}
