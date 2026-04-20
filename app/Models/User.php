<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Concerns\LogsAuditActivity;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Application user; doubles as a customer-tenant actor when assigned the customer role.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Customer> $customers
 *
 * @mixin Builder<self>
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, LogsAuditActivity, Notifiable, TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /** Log name used when recording activity for users. */
    public static function auditLogName(): string
    {
        return 'user';
    }

    /** Human-readable label describing this user in audit log entries. */
    public function auditSubjectLabel(): string
    {
        return "user {$this->name} ({$this->email})";
    }

    /** Many-to-many pivot — only populated for users who represent a customer tenant. */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_user')->withTimestamps();
    }

    /**
     * IDs of all customers this user is linked to via the customer_user pivot.
     *
     * @return array<int, int>
     */
    public function customerIds(): array
    {
        return $this->customers()->pluck('customers.id')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * A user is "tenant-scoped" when they represent an external customer
     * (customer role AND linked to at least one customer via the pivot).
     */
    public function isTenantScoped(): bool
    {
        return $this->hasRole(UserRole::Customer->value) && $this->customers()->exists();
    }
}
