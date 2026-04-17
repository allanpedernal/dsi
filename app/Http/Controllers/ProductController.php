<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Services\ProductService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private ProductService $service) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);
        $user = $request->user();

        return Inertia::render('products/index', [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'tenantScoped' => $user->isTenantScoped(),
            'customers' => $user->isTenantScoped() ? [] : Customer::orderBy('last_name')->orderBy('first_name')
                ->get(['id', 'code', 'first_name', 'last_name'])
                ->map(fn ($c) => ['id' => $c->id, 'label' => trim("{$c->first_name} {$c->last_name}").' ('.$c->code.')'])
                ->values()
                ->all(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);
        $products = $this->service->paginate(
            $request->only(['search', 'category_id', 'customer_id', 'only_low_stock', 'sort', 'dir', 'per_page'])
        );

        return ApiResponse::ok(ProductResource::collection($products));
    }

    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);
        $product->load('category');

        return ApiResponse::ok(new ProductResource($product));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->service->create($request->validated(), $request->user()->id);

        return ApiResponse::created(new ProductResource($product), 'Product created');
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->service->update($product, $request->validated(), $request->user()->id);

        return ApiResponse::ok(new ProductResource($product), 'Product updated');
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);
        $this->service->delete($product);

        return ApiResponse::ok(null, 'Product deleted');
    }
}
