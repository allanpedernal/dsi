<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditActivity;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Product category grouping for the storefront and admin catalogue.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Product> $products
 *
 * @mixin Builder<self>
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, LogsAuditActivity;

    protected $fillable = ['name', 'slug', 'description'];

    /** Log name used when recording activity for categories. */
    public static function auditLogName(): string
    {
        return 'category';
    }

    /** Human-readable label describing this category in audit log entries. */
    public function auditSubjectLabel(): string
    {
        return "category \"{$this->name}\"";
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
