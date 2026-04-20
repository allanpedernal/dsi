<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditActivity;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Product entity representing a sellable item in the catalogue.
 *
 * @property int $id
 * @property string $sku
 * @property string $name
 * @property string $slug
 * @property int|null $category_id
 * @property int|null $customer_id
 * @property string|null $description
 * @property string $price
 * @property string $cost
 * @property int $stock
 * @property int $reorder_level
 * @property bool $is_active
 * @property string|null $image_path
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Category|null $category
 * @property-read Customer|null $customer
 * @property-read Collection<int, SaleItem> $saleItems
 *
 * @mixin Builder<self>
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, LogsAuditActivity, SoftDeletes;

    protected $fillable = [
        'sku', 'name', 'slug', 'category_id', 'customer_id', 'description',
        'price', 'cost', 'stock', 'reorder_level', 'is_active', 'image_path',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /** Log name used when recording activity for products. */
    public static function auditLogName(): string
    {
        return 'product';
    }

    /** Human-readable label describing this product in audit log entries. */
    public function auditSubjectLabel(): string
    {
        return "product {$this->name} (SKU {$this->sku})";
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /** True when on-hand stock has dropped to or below the reorder threshold. */
    public function isLowStock(): bool
    {
        return $this->stock <= $this->reorder_level;
    }
}
