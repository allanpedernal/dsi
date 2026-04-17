<?php

namespace App\Models;

use App\Enums\SaleStatus;
use App\Models\Concerns\LogsAuditActivity;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public static function auditLogName(): string
    {
        return 'sale';
    }

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

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
