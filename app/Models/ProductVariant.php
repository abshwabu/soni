<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    /** @use HasFactory<\Database\Factories\ProductVariantFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'size_ml',
        'price',
        'compare_at_price',
        'sku',
        'stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'size_ml' => 'integer',
            'price' => 'integer',
            'compare_at_price' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock', '>', 0)->where('is_active', true);
    }

    public function getFormattedPriceAttribute(): string
    {
        return Money::format($this->price);
    }

    public function getFormattedCompareAtPriceAttribute(): ?string
    {
        return $this->compare_at_price ? Money::format($this->compare_at_price) : null;
    }

    public function getLabelAttribute(): string
    {
        return "{$this->size_ml}ml";
    }

    public function getInStockAttribute(): bool
    {
        return $this->is_active && $this->stock > 0;
    }
}
