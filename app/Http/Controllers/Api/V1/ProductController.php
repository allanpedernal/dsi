<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ProductService $service) {}

    /**
     * @OA\Get(path="/api/v1/products", tags={"Products"}, security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="only_low_stock", in="query", @OA\Schema(type="boolean")),
     *
     *     @OA\Response(response=200, description="OK"))
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        return ApiResponse::ok(ProductResource::collection(
            $this->service->paginate($request->only(['search', 'category_id', 'customer_id', 'only_low_stock', 'sort', 'dir', 'per_page']))
        ));
    }

    /**
     * @OA\Get(path="/api/v1/products/{id}", tags={"Products"}, security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="OK"))
     */
    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        return ApiResponse::ok(new ProductResource($product->load('category')));
    }

    /**
     * @OA\Post(path="/api/v1/products", tags={"Products"}, security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"sku","name","price","stock"},
     *
     *         @OA\Property(property="sku", type="string"),
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="price", type="number"),
     *         @OA\Property(property="stock", type="integer"))),
     *
     *     @OA\Response(response=201, description="Created"))
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        return ApiResponse::created(new ProductResource(
            $this->service->create($request->validated(), $request->user()->id)
        ));
    }

    /**
     * @OA\Put(path="/api/v1/products/{id}", tags={"Products"}, security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent()),
     *
     *     @OA\Response(response=200, description="OK"))
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        return ApiResponse::ok(new ProductResource(
            $this->service->update($product, $request->validated(), $request->user()->id)
        ));
    }

    /**
     * @OA\Delete(path="/api/v1/products/{id}", tags={"Products"}, security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Deleted"))
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);
        $this->service->delete($product);

        return ApiResponse::ok(null, 'Deleted');
    }
}
