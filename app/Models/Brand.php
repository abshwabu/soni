<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Brand extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\BrandFactory> */
    use HasFactory, HasSlug, InteractsWithMedia;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_featured',
    ];

    protected $appends = [
        'logo_url',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Brand $brand) {
            app(\App\Services\SearchService::class)->indexBrand($brand);
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
        $this->addMediaCollection('logo')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->width(300)
            ->height(300)
            ->nonQueued();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function getLogoUrlAttribute(): ?string
    {
        $url = $this->getFirstMediaUrl('logo', 'webp') ?: $this->getFirstMediaUrl('logo');
        if ($url && str_contains($url, '/storage/')) {
            return '/storage/' . explode('/storage/', $url, 2)[1];
        }
        return $url ?: '/images/placeholder.svg';
    }
}
