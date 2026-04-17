<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(private SaleService $service) {}

    /**
     * @OA\Get(path="/api/v1/sales", tags={"Sales"}, security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"pending","paid","refunded","cancelled"})),
     *     @OA\Parameter(name="from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to", in="query", @OA\Schema(type="string", format="date")),
     *
     *     @OA\Response(response=200, description="OK"))
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Sale::class);

        return ApiResponse::ok(SaleResource::collection(
            $this->service->paginate($request->only(['search', 'status', 'from', 'to', 'customer_id', 'user_id', 'sort', 'dir', 'per_page']))
        ));
    }

    /**
     * @OA\Get(path="/api/v1/sales/{id}", tags={"Sales"}, security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="OK"))
     */
    public function show(Sale $sale): JsonResponse
    {
        $this->authorize('view', $sale);

        return ApiResponse::ok(new SaleResource($sale->load(['customer', 'cashier', 'items'])));
    }

    /**
     * @OA\Post(path="/api/v1/sales", tags={"Sales"}, security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"customer_id","items"},
     *
     *         @OA\Property(property="customer_id", type="integer"),
     *         @OA\Property(property="tax_rate", type="number", example=0.10),
     *         @OA\Property(property="discount", type="number"),
     *         @OA\Property(property="notes", type="string"),
     *         @OA\Property(property="items", type="array", @OA\Items(type="object",
     *             @OA\Property(property="product_id", type="integer"),
     *             @OA\Property(property="quantity", type="integer")
     *         )))),
     *
     *     @OA\Response(response=201, description="Created"))
     */
    public function store(StoreSaleRequest $request): JsonResponse
    {
        $sale = $this->service->create(
            array_merge($request->validated(), ['user_id' => $request->user()->id, 'source' => 'api']),
            $request->user(),
        );

        return ApiResponse::created(new SaleResource($sale));
    }

    /**
     * @OA\Delete(path="/api/v1/sales/{id}", tags={"Sales"}, security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Deleted"))
     */
    public function destroy(Sale $sale): JsonResponse
    {
        $this->authorize('delete', $sale);
        $sale->delete();

        return ApiResponse::ok(null, 'Deleted');
    }
}
