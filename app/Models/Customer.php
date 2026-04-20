<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditActivity;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Customer entity representing a person or tenant organisation.
 *
 * @property int $id
 * @property string $code
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $city
 * @property string|null $country
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read string $full_name
 * @property-read User|null $creator
 * @property-read Collection<int, Sale> $sales
 * @property-read Collection<int, Product> $products
 * @property-read Collection<int, User> $accounts
 *
 * @mixin Builder<self>
 */
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, LogsAuditActivity, SoftDeletes;

    protected $fillable = [
        'code', 'first_name', 'last_name', 'email', 'phone',
        'address', 'city', 'country', 'notes',
        'created_by', 'updated_by',
    ];

    /** Log name used when recording activity for customers. */
    public static function auditLogName(): string
    {
        return 'customer';
    }

    /** Human-readable label describing this customer in audit log entries. */
    public function auditSubjectLabel(): string
    {
        return "customer {$this->full_name} (#{$this->code})";
    }

    /** Concatenates first and last name for display. */
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
