<?php

namespace App\Services;

use App\Models\Product;
use App\Support\TenantScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Read/write operations for products, encapsulating tenant scoping and CRUD.
 */
class ProductService
{
    /**
     * Paginate products, honouring tenant scoping and search/sort/low-stock filters.
     *
     * @param  array{search?: ?string, category_id?: ?int, customer_id?: ?int, only_low_stock?: ?bool, sort?: ?string, dir?: ?string, per_page?: ?int}  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $sort = in_array($filters['sort'] ?? null, ['name', 'sku', 'price', 'stock', 'created_at']) ? $filters['sort'] : 'created_at';
        $dir = ($filters['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 15)));
        $customerId = TenantScope::forUser(Auth::user(), $filters['customer_id'] ?? null);

        return Product::query()
            ->with(['category', 'customer:id,first_name,last_name'])
            ->when($customerId !== null, fn (Builder $q) => $q->where('customer_id', $customerId))
            ->when($filters['search'] ?? null, function (Builder $q, string $term) {
                $q->where(function ($q) use ($term) {
                    $q->where('sku', 'like', "%{$term}%")
                        ->orWhere('name', 'like', "%{$term}%");
                });
            })
            ->when(! empty($filters['category_id']), fn (Builder $q) => $q->where('category_id', $filters['category_id']))
            ->when(! empty($filters['only_low_stock']), fn (Builder $q) => $q->whereColumn('stock', '<=', 'reorder_level'))
            ->orderBy($sort, $dir)
            ->paginate($perPage);
    }

    /**
     * Create a new product; tenant-scoped users always get their pivot customer_id.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $actingUserId = null): Product
    {
        $data['slug'] ??= Str::slug($data['name']).'-'.Str::random(5);
        $data['created_by'] = $actingUserId;

        // Tenant-scoped creators force their own customer_id from the pivot.
        $actor = Auth::user();
        if ($actor?->isTenantScoped()) {
            $data['customer_id'] = $actor->customerIds()[0] ?? null;
        }

        return Product::create($data);
    }

    /**
     * Update an existing product, stamping the acting user as last editor.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data, ?int $actingUserId = null): Product
    {
        $data['updated_by'] = $actingUserId;
        $product->update($data);

        return $product->refresh();
    }

    /** Soft-delete a product. */
    public function delete(Product $product): void
    {
        $product->delete();
    }
}
