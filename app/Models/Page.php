<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Page extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            if ($page->content) {
                $page->content = static::sanitizeHtml($page->content);
            }
        });
    }

    /**
     * Sanitize rich text HTML to remove scripts, iframes, inline event handlers, and dangerous protocols.
     */
    public static function sanitizeHtml(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // 1. Strip dangerous tags completely
        $clean = preg_replace('#<(script|iframe|object|embed|applet|form)(.*?)>(.*?)</\1>#is', '', $html);
        $clean = preg_replace('#<(script|iframe|object|embed|applet|form)(.*?)/?>#is', '', $clean);

        // 2. Strip inline JS event handlers like onload, onerror, onclick, etc.
        $clean = preg_replace('/\s+on[a-zA-Z]+\s*=\s*(["\']).*?\1/i', '', $clean);
        $clean = preg_replace('/\s+on[a-zA-Z]+\s*=\s*[^"\'>\s]+/i', '', $clean);

        // 3. Strip javascript: and vbscript: URIs
        $clean = preg_replace('/href\s*=\s*(["\'])\s*(javascript|vbscript|data):.*?\1/i', 'href="#"', $clean);
        $clean = preg_replace('/src\s*=\s*(["\'])\s*(javascript|vbscript):.*?\1/i', 'src=""', $clean);

        return trim($clean);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}

