<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read/write operations for customers, encapsulating list pagination and CRUD.
 */
class CustomerService
{
    /**
     * Paginate customers with optional search/sort filters.
     *
     * @param  array{search?: ?string, sort?: ?string, dir?: ?string, per_page?: ?int}  $filters
     * @return LengthAwarePaginator<int, Customer>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $sort = in_array($filters['sort'] ?? null, ['code', 'first_name', 'last_name', 'email', 'created_at']) ? $filters['sort'] : 'created_at';
        $dir = ($filters['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 15)));

        return Customer::query()
            ->when($filters['search'] ?? null, function (Builder $q, string $term) {
                $q->where(function ($q) use ($term) {
                    $q->where('code', 'like', "%{$term}%")
                        ->orWhere('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->orderBy($sort, $dir)
            ->paginate($perPage);
    }

    /**
     * Create a new customer, stamping the acting user as creator.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $actingUserId = null): Customer
    {
        $data['created_by'] = $actingUserId;

        return Customer::create($data);
    }

    /**
     * Update an existing customer, stamping the acting user as last editor.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Customer $customer, array $data, ?int $actingUserId = null): Customer
    {
        $data['updated_by'] = $actingUserId;
        $customer->update($data);

        return $customer->refresh();
    }

    /** Soft-delete a customer. */
    public function delete(Customer $customer): void
    {
        $customer->delete();
    }
}
