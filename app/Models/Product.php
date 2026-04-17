<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditActivity;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public static function auditLogName(): string
    {
        return 'product';
    }

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

    public function isLowStock(): bool
    {
        return $this->stock <= $this->reorder_level;
    }
}
