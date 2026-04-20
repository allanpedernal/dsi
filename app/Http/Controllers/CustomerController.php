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

/**
 * Web controller backing the customer management UI and its JSON data endpoints.
 */
class CustomerController extends Controller
{
    public function __construct(private CustomerService $service) {}

    /** Render the customers index page with country options for the form. */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Customer::class);

        return Inertia::render('customers/index', [
            'countries' => Country::options(),
        ]);
    }

    /** Return paginated customers as JSON for the data table. */
    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);
        $customers = $this->service->paginate($request->only(['search', 'sort', 'dir', 'per_page']));

        return ApiResponse::ok(CustomerResource::collection($customers));
    }

    /** Return a single customer as JSON. */
    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        return ApiResponse::ok(new CustomerResource($customer));
    }

    /** Create a new customer stamped with the acting user as creator. */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->service->create($request->validated(), $request->user()->id);

        return ApiResponse::created(new CustomerResource($customer), 'Customer created');
    }

    /** Update an existing customer stamped with the acting user as editor. */
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer = $this->service->update($customer, $request->validated(), $request->user()->id);

        return ApiResponse::ok(new CustomerResource($customer), 'Customer updated');
    }

    /** Soft-delete a customer. */
    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorize('delete', $customer);
        $this->service->delete($customer);

        return ApiResponse::ok(null, 'Customer deleted');
    }
}
