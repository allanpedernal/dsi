<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditActivity;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, LogsAuditActivity;

    protected $fillable = ['name', 'slug', 'description'];

    public static function auditLogName(): string
    {
        return 'category';
    }

    public function auditSubjectLabel(): string
    {
        return "category \"{$this->name}\"";
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
