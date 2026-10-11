<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchService
{
    /**
     * Check if FTS5 virtual table is available and functional.
     */
    public function isFtsAvailable(): bool
    {
        try {
            DB::select("SELECT 1 FROM products_fts LIMIT 0");
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Ensure the FTS5 virtual table exists.
     */
    public function ensureTable(): void
    {
        try {
            DB::statement("CREATE VIRTUAL TABLE IF NOT EXISTS products_fts USING fts5(
                product_id UNINDEXED,
                name,
                brand_name,
                notes,
                scent_family,
                description
            )");
        } catch (\Throwable $e) {
            Log::warning("Could not create FTS5 table: " . $e->getMessage());
        }
    }

    /**
     * Rebuild entire FTS5 search index.
     */
    public function reindexAll(): int
    {
        $this->ensureTable();

        if ($this->isFtsAvailable()) {
            try {
                DB::statement("DELETE FROM products_fts");
            } catch (\Throwable $e) {
                Log::warning("Could not clear products_fts: " . $e->getMessage());
            }
        }

        $count = 0;
        $products = Product::with(['brand', 'variants'])->whereNull('deleted_at')->get();

        foreach ($products as $product) {
            $this->indexProduct($product);
            $count++;
        }

        return $count;
    }

    /**
     * Index a single product in FTS5.
     */
    public function indexProduct(Product $product): void
    {
        if (!$this->isFtsAvailable()) {
            return;
        }

        try {
            // Delete existing
            DB::statement("DELETE FROM products_fts WHERE product_id = ?", [$product->id]);

            // Only index active, non-soft-deleted products
            if (!$product->is_active || $product->trashed()) {
                return;
            }

            $product->loadMissing('brand');

            $brandName = $product->brand?->name ?? '';
            $notes = trim(implode(' ', array_filter([
                $product->top_notes,
                $product->heart_notes,
                $product->base_notes,
            ])));

            $scentFamily = $product->scent_family instanceof \BackedEnum
                ? $product->scent_family->value
                : (string) ($product->scent_family ?? '');

            $description = trim(strip_tags("{$product->short_description} {$product->description}"));

            DB::insert(
                "INSERT INTO products_fts(product_id, name, brand_name, notes, scent_family, description) VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $product->id,
                    $product->name,
                    $brandName,
                    $notes,
                    $scentFamily,
                    $description,
                ]
            );
        } catch (\Throwable $e) {
            Log::error("Failed indexing product {$product->id} in FTS5: " . $e->getMessage());
        }
    }

    /**
     * Remove a product from FTS5 index.
     */
    public function deleteProduct(int $productId): void
    {
        if (!$this->isFtsAvailable()) {
            return;
        }

        try {
            DB::statement("DELETE FROM products_fts WHERE product_id = ?", [$productId]);
        } catch (\Throwable $e) {
            Log::warning("Failed deleting product {$productId} from FTS5: " . $e->getMessage());
        }
    }

    /**
     * Re-index all active products belonging to a brand.
     */
    public function indexBrand(Brand $brand): void
    {
        $products = Product::where('brand_id', $brand->id)->with('brand')->get();
        foreach ($products as $product) {
            $this->indexProduct($product);
        }
    }

    /**
     * Search product IDs matching query with prefix matching, falling back to LIKE.
     *
     * @return array<int>
     */
    public function searchProductIds(string $query, int $limit = 200): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        // Extract alphanumeric / unicode words
        preg_match_all('/[\p{L}\p{N}]+/u', $query, $matches);
        $words = $matches[0] ?? [];

        if (empty($words)) {
            return [];
        }

        // 1. Try SQLite FTS5 with prefix matching
        if ($this->isFtsAvailable()) {
            try {
                // Build FTS query with prefix wildcard, e.g. "sauv"* AND "dior"*
                $ftsParts = array_map(function ($word) {
                    $cleaned = str_replace('"', '""', $word);
                    return '"' . $cleaned . '"*';
                }, $words);

                $ftsMatch = implode(' AND ', $ftsParts);

                $rows = DB::select(
                    "SELECT product_id FROM products_fts WHERE products_fts MATCH ? LIMIT ?",
                    [$ftsMatch, $limit]
                );

                if (!empty($rows)) {
                    $ids = array_map(fn ($r) => (int) $r->product_id, $rows);

                    // If exact active filter verification is needed
                    return Product::active()
                        ->whereIn('id', $ids)
                        ->pluck('id')
                        ->toArray();
                }

                // If AND yielded 0 results, try OR search for better recall
                if (count($words) > 1) {
                    $ftsOrMatch = implode(' OR ', $ftsParts);
                    $rowsOr = DB::select(
                        "SELECT product_id FROM products_fts WHERE products_fts MATCH ? LIMIT ?",
                        [$ftsOrMatch, $limit]
                    );

                    if (!empty($rowsOr)) {
                        $ids = array_map(fn ($r) => (int) $r->product_id, $rowsOr);
                        return Product::active()
                            ->whereIn('id', $ids)
                            ->pluck('id')
                            ->toArray();
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("FTS5 match failed, falling back to LIKE: " . $e->getMessage());
            }
        }

        // 2. Fallback to SQL LIKE with escaped wildcards
        return $this->searchWithLike($words, $limit);
    }

    /**
     * Fallback LIKE search with escaped wildcards.
     *
     * @param array<string> $words
     * @return array<int>
     */
    protected function searchWithLike(array $words, int $limit = 200): array
    {
        return Product::active()
            ->where(function ($query) use ($words) {
                foreach ($words as $word) {
                    $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $word);
                    $query->where(function ($sub) use ($escaped) {
                        $sub->where('name', 'like', "%{$escaped}%")
                            ->orWhere('top_notes', 'like', "%{$escaped}%")
                            ->orWhere('heart_notes', 'like', "%{$escaped}%")
                            ->orWhere('base_notes', 'like', "%{$escaped}%")
                            ->orWhere('scent_family', 'like', "%{$escaped}%")
                            ->orWhere('short_description', 'like', "%{$escaped}%")
                            ->orWhere('description', 'like', "%{$escaped}%")
                            ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$escaped}%"));
                    });
                }
            })
            ->take($limit)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Suggestions for live header search overlay.
     */
    public function getSuggestions(string $query, int $productLimit = 6, int $brandLimit = 6): array
    {
        $query = trim($query);
        if ($query === '' || mb_strlen($query) < 2) {
            return [
                'products' => [],
                'brands' => [],
                'total' => 0,
            ];
        }

        $productIds = $this->searchProductIds($query, 50);

        $products = [];
        if (!empty($productIds)) {
            $matchedProducts = Product::active()
                ->whereIn('id', $productIds)
                ->with(['brand', 'variants', 'media'])
                ->take($productLimit)
                ->get();

            // Preserve search relevance order
            $orderMap = array_flip($productIds);
            $sorted = $matchedProducts->sortBy(fn ($p) => $orderMap[$p->id] ?? 9999);

            $products = $sorted->values()->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'brand_name' => $p->brand?->name,
                    'concentration' => $p->concentration instanceof \BackedEnum ? $p->concentration->value : (string) $p->concentration,
                    'price_from' => $p->price_from,
                    'formatted_price_from' => $p->formatted_price_from,
                    'thumbnail_url' => $p->thumbnail_url,
                ];
            })->all();
        }

        // Brands search
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query);
        $brands = Brand::where('name', 'like', "%{$escaped}%")
            ->withCount(['products' => fn ($q) => $q->active()])
            ->take($brandLimit)
            ->get()
            ->map(function ($b) {
                return [
                    'id' => $b->id,
                    'name' => $b->name,
                    'slug' => $b->slug,
                    'logo_url' => $b->logo_url,
                    'products_count' => $b->products_count,
                ];
            })->all();

        return [
            'products' => $products,
            'brands' => $brands,
            'total' => count($productIds),
        ];
    }
}
