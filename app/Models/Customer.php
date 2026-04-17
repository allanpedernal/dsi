<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditActivity;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, LogsAuditActivity, SoftDeletes;

    protected $fillable = [
        'code', 'first_name', 'last_name', 'email', 'phone',
        'address', 'city', 'country', 'notes',
        'created_by', 'updated_by',
    ];

    public static function auditLogName(): string
    {
        return 'customer';
    }

    public function auditSubjectLabel(): string
    {
        return "customer {$this->full_name} (#{$this->code})";
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'customer_user')->withTimestamps();
    }
}
