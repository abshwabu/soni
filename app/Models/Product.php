<?php

namespace App\Models;

use App\Enums\Concentration;
use App\Enums\Gender;
use App\Enums\ScentFamily;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, HasSlug, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'brand_id',
        'category_id',
        'name',
        'slug',
        'short_description',
        'description',
        'gender',
        'concentration',
        'top_notes',
        'heart_notes',
        'base_notes',
        'scent_family',
        'is_featured',
        'is_new',
        'is_bestseller',
        'is_active',
        'meta_title',
        'meta_description',
    ];

    protected $appends = [
        'price_from',
        'formatted_price_from',
        'primary_image_url',
        'secondary_image_url',
        'thumbnail_url',
        'in_stock',
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'concentration' => Concentration::class,
            'scent_family' => ScentFamily::class,
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Product $product) {
            if ($product->isDirty('slug')) {
                $oldSlug = $product->getOriginal('slug');
                $newSlug = $product->slug;
                if ($oldSlug && $newSlug && $oldSlug !== $newSlug) {
                    \App\Models\SlugRedirect::updateOrCreate(
                        ['source_slug' => $oldSlug, 'model_type' => Product::class],
                        ['target_slug' => $newSlug, 'model_id' => $product->id]
                    );
                }
            }
        });

        static::saved(function (Product $product) {
            app(\App\Services\SearchService::class)->indexProduct($product);
        });

        static::deleted(function (Product $product) {
            app(\App\Services\SearchService::class)->deleteProduct($product->id);
        });

        static::restored(function (Product $product) {
            app(\App\Services\SearchService::class)->indexProduct($product);
        });
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->width(800)
            ->height(800)
            ->nonQueued();

        $this->addMediaConversion('thumb')
            ->format('webp')
            ->width(300)
            ->height(300)
            ->nonQueued();
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->where('is_active', true);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('is_new', true);
    }

    public function scopeBestseller(Builder $query): Builder
    {
        return $query->where('is_bestseller', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->whereHas('variants', function (Builder $q) {
            $q->where('stock', '>', 0)->where('is_active', true);
        });
    }

    public function getPriceFromAttribute(): ?int
    {
        if ($this->relationLoaded('variants')) {
            $active = $this->variants->where('is_active', true);
            $cheapest = $active->isNotEmpty() ? $active->min('price') : $this->variants->min('price');
            return $cheapest !== null ? (int) $cheapest : null;
        }

        return (int) ($this->activeVariants()->min('price') ?? $this->variants()->min('price'));
    }

    public function getFormattedPriceFromAttribute(): string
    {
        return Money::format($this->price_from);
    }

    public function getInStockAttribute(): bool
    {
        if ($this->relationLoaded('variants')) {
            return $this->variants->where('is_active', true)->where('stock', '>', 0)->isNotEmpty();
        }

        return $this->variants()->where('is_active', true)->where('stock', '>', 0)->exists();
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        $url = $this->getFirstMediaUrl('images', 'webp') ?: $this->getFirstMediaUrl('images');
        if ($url && str_contains($url, '/storage/')) {
            return '/storage/' . explode('/storage/', $url, 2)[1];
        }
        return $url ?: '/images/placeholder.svg';
    }

    public function getSecondaryImageUrlAttribute(): ?string
    {
        $media = $this->getMedia('images');
        if ($media->count() > 1) {
            $url = $media[1]->getUrl('webp') ?: $media[1]->getUrl();
            if ($url && str_contains($url, '/storage/')) {
                return '/storage/' . explode('/storage/', $url, 2)[1];
            }
            return $url;
        }

        return null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        $url = $this->getFirstMediaUrl('images', 'thumb') ?: $this->primary_image_url;
        if ($url && str_contains($url, '/storage/')) {
            return '/storage/' . explode('/storage/', $url, 2)[1];
        }
        return $url ?: '/images/placeholder.svg';
    }
}
