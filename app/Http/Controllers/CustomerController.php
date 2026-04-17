<?php

namespace App\Http\Controllers;

use App\Enums\Country;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(private CustomerService $service) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Customer::class);

        return Inertia::render('customers/index', [
            'countries' => Country::options(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);
        $customers = $this->service->paginate($request->only(['search', 'sort', 'dir', 'per_page']));

        return ApiResponse::ok(CustomerResource::collection($customers));
    }

    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        return ApiResponse::ok(new CustomerResource($customer));
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->service->create($request->validated(), $request->user()->id);

        return ApiResponse::created(new CustomerResource($customer), 'Customer created');
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer = $this->service->update($customer, $request->validated(), $request->user()->id);

        return ApiResponse::ok(new CustomerResource($customer), 'Customer updated');
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorize('delete', $customer);
        $this->service->delete($customer);

        return ApiResponse::ok(null, 'Customer deleted');
    }
}
