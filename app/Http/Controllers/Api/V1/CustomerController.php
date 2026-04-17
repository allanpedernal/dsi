<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(private CustomerService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/customers",
     *     summary="List customers (paginated)",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        return ApiResponse::ok(CustomerResource::collection(
            $this->service->paginate($request->only(['search', 'sort', 'dir', 'per_page']))
        ));
    }

    /**
     * @OA\Get(path="/api/v1/customers/{id}", tags={"Customers"}, security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="OK"))
     */
    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        return ApiResponse::ok(new CustomerResource($customer));
    }

    /**
     * @OA\Post(path="/api/v1/customers", tags={"Customers"}, security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"first_name","last_name","email"},
     *
     *         @OA\Property(property="first_name", type="string"),
     *         @OA\Property(property="last_name", type="string"),
     *         @OA\Property(property="email", type="string"))),
     *
     *     @OA\Response(response=201, description="Created"))
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->service->create($request->validated(), $request->user()->id);

        return ApiResponse::created(new CustomerResource($customer));
    }

    /**
     * @OA\Put(path="/api/v1/customers/{id}", tags={"Customers"}, security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent()),
     *
     *     @OA\Response(response=200, description="OK"))
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        return ApiResponse::ok(new CustomerResource(
            $this->service->update($customer, $request->validated(), $request->user()->id)
        ));
    }

    /**
     * @OA\Delete(path="/api/v1/customers/{id}", tags={"Customers"}, security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Deleted"))
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorize('delete', $customer);
        $this->service->delete($customer);

        return ApiResponse::ok(null, 'Deleted');
    }
}
