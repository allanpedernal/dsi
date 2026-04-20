<?php

namespace App\Models;

use App\Enums\SaleStatus;
use App\Models\Concerns\LogsAuditActivity;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Sales transaction recorded against a customer by a user.
 *
 * @property int $id
 * @property string $reference
 * @property int $customer_id
 * @property int $user_id
 * @property SaleStatus $status
 * @property string $subtotal
 * @property string $tax
 * @property string $discount
 * @property string $total
 * @property Carbon|null $paid_at
 * @property string|null $notes
 * @property string $source
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Customer $customer
 * @property-read Collection<int, SaleItem> $items
 *
 * @mixin Builder<self>
 */
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory, LogsAuditActivity, SoftDeletes;

    protected $fillable = [
        'reference', 'customer_id', 'user_id', 'status',
        'subtotal', 'tax', 'discount', 'total',
        'paid_at', 'notes', 'source',
    ];

    protected function casts(): array
    {
        return [
            'status' => SaleStatus::class,
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    /** Log name used when recording activity for sales. */
    public static function auditLogName(): string
    {
        return 'sale';
    }

    /** Human-readable label describing this sale in audit log entries. */
    public function auditSubjectLabel(): string
    {
        $customer = $this->relationLoaded('customer') ? $this->customer?->full_name : null;
        $total = number_format((float) $this->total, 2);
        $forCustomer = $customer ? " for {$customer}" : '';

        return "sale {$this->reference}{$forCustomer} (\${$total})";
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
