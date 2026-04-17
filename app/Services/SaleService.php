<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Events\SaleCreated;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Support\TenantScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleService
{
    /**
     * @param  array{search?: ?string, status?: ?string, from?: ?string, to?: ?string, customer_id?: ?int, user_id?: ?int, per_page?: ?int, sort?: ?string, dir?: ?string}  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $sort = in_array($filters['sort'] ?? null, ['reference', 'total', 'created_at', 'paid_at', 'status']) ? $filters['sort'] : 'created_at';
        $dir = ($filters['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 15)));
        $customerId = TenantScope::forUser(Auth::user(), $filters['customer_id'] ?? null);

        return Sale::query()
            ->with(['customer:id,first_name,last_name,code'])
            ->when($customerId !== null, fn (Builder $q) => $q->where('customer_id', $customerId))
            ->when($filters['search'] ?? null, fn (Builder $q, string $t) => $q->where('reference', 'like', "%{$t}%"))
            ->when($filters['status'] ?? null, fn (Builder $q, string $s) => $q->where('status', $s))
            ->when($filters['user_id'] ?? null, fn (Builder $q, int $id) => $q->where('user_id', $id))
            ->when($filters['from'] ?? null, fn (Builder $q, string $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn (Builder $q, string $d) => $q->whereDate('created_at', '<=', $d))
            ->orderBy($sort, $dir)
            ->paginate($perPage);
    }

    /**
     * Create a sale transactionally with stock locking.
     *
     * @param  array{customer_id:int, user_id?:int, items:array<int,array{product_id:int,quantity:int}>, tax_rate?:float, discount?:float, notes?:?string, source?:string}  $data
     */
    public function create(array $data, ?User $actingUser = null): Sale
    {
        $userId = $data['user_id'] ?? $actingUser?->id ?? throw new \InvalidArgumentException('user_id is required');
        $taxRate = (float) ($data['tax_rate'] ?? 0);
        $discount = (float) ($data['discount'] ?? 0);
        $source = $data['source'] ?? 'web';

        return DB::transaction(function () use ($data, $userId, $taxRate, $discount, $source) {
            $productIds = collect($data['items'])->pluck('product_id')->all();

            $products = Product::query()
                ->whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0.0;
            $itemRows = [];

            foreach ($data['items'] as $item) {
                $product = $products[$item['product_id']] ?? throw new \InvalidArgumentException("Product {$item['product_id']} not found");
                $qty = (int) $item['quantity'];

                if ($product->stock < $qty) {
                    throw new InsufficientStockException($product->name, $qty, $product->stock);
                }

                $unit = (float) $product->price;
                $line = $unit * $qty;
                $subtotal += $line;

                $itemRows[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'unit_price' => $unit,
                    'quantity' => $qty,
                    'line_total' => $line,
                ];

                $product->decrement('stock', $qty);
            }

            $tax = round($subtotal * $taxRate, 2);
            $total = round($subtotal + $tax - $discount, 2);

            $sale = Sale::create([
                'reference' => $this->nextReference(),
                'customer_id' => $data['customer_id'],
                'user_id' => $userId,
                'status' => SaleStatus::Paid,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'paid_at' => now(),
                'notes' => $data['notes'] ?? null,
                'source' => $source,
            ]);

            $sale->items()->createMany($itemRows);

            $sale->load(['customer', 'items']);

            DB::afterCommit(fn () => event(new SaleCreated($sale)));

            return $sale;
        });
    }

    /**
     * Update a sale transactionally with stock adjustments.
     *
     * @param  array{customer_id?:int, items?:array<int,array{product_id:int,quantity:int}>, tax_rate?:float, discount?:float, notes?:?string}  $data
     */
    public function update(Sale $sale, array $data): Sale
    {
        return DB::transaction(function () use ($sale, $data) {
            if (isset($data['items'])) {
                // Restore stock from existing items.
                foreach ($sale->items as $item) {
                    Product::where('id', $item->product_id)->increment('stock', $item->quantity);
                }

                // Lock and validate new items.
                $productIds = collect($data['items'])->pluck('product_id')->all();
                $products = Product::query()
                    ->whereIn('id', $productIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $subtotal = 0.0;
                $itemRows = [];

                foreach ($data['items'] as $item) {
                    $product = $products[$item['product_id']] ?? throw new \InvalidArgumentException("Product {$item['product_id']} not found");
                    $qty = (int) $item['quantity'];

                    if ($product->stock < $qty) {
                        throw new InsufficientStockException($product->name, $qty, $product->stock);
                    }

                    $unit = (float) $product->price;
                    $line = $unit * $qty;
                    $subtotal += $line;

                    $itemRows[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_sku' => $product->sku,
                        'unit_price' => $unit,
                        'quantity' => $qty,
                        'line_total' => $line,
                    ];

                    $product->decrement('stock', $qty);
                }

                $sale->items()->delete();
                $sale->items()->createMany($itemRows);

                $sale->subtotal = $subtotal;
            }

            $taxRate = \array_key_exists('tax_rate', $data)
                ? (float) $data['tax_rate']
                : ((float) $sale->subtotal > 0 ? (float) $sale->tax / (float) $sale->subtotal : 0);

            $sale->tax = round((float) $sale->subtotal * $taxRate, 2);
            $sale->discount = \array_key_exists('discount', $data) ? (float) $data['discount'] : (float) $sale->discount;
            $sale->total = round((float) $sale->subtotal + (float) $sale->tax - (float) $sale->discount, 2);
            $sale->notes = \array_key_exists('notes', $data) ? $data['notes'] : $sale->notes;

            if (isset($data['customer_id'])) {
                $sale->customer_id = $data['customer_id'];
            }

            $sale->save();
            $sale->load(['customer', 'items']);

            return $sale;
        });
    }

    private function nextReference(): string
    {
        $year = now()->year;
        $count = Sale::whereYear('created_at', $year)->count() + 1;

        return sprintf('SO-%d-%05d', $year, $count);
    }
}
