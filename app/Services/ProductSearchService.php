<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ProductSearchService
{
    /**
     * Search for products by ID or FullText query.
     * Handles patterns like 'ID: 123' or natural language queries.
     *
     * @param Store $store
     * @param string $query
     * @return Product|Collection|null
     */
    public function search(Store $store, string $query): Product|Collection|null
    {
        // Check if query contains an ID pattern like 'ID: 123'
        $productId = $this->extractProductId($query);

        if ($productId) {
            $product = Product::where('store_id', $store->id)
                ->where('id', $productId)
                ->first();

            if ($product) {
                return $product;
            }
        }

        // Fall back to FullText search in NATURAL LANGUAGE MODE
        return $this->searchByFullText($store, $query);
    }

    /**
     * Find the best matching product by ID or FullText search.
     *
     * @param Store $store
     * @param string $query
     * @return Product|Collection|null
     */
    public function findBestMatch(Store $store, string $query): Product|Collection|null
    {
        // First, try to extract a product ID from the query using regex
        $productId = $this->extractProductId($query);

        if ($productId) {
            $product = Product::where('store_id', $store->id)
                ->where('id', $productId)
                ->first();

            if ($product) {
                return $product;
            }
        }

        // Fall back to FullText search on name and description
        return $this->fullTextSearch($store, $query);
    }

    /**
     * Extract product ID from query using regex.
     * Looks for patterns like "product 123", "id:123", "#123", etc.
     *
     * @param string $query
     * @return int|null
     */
    private function extractProductId(string $query): ?int
    {
        // Match patterns: "product 123", "id:123", "#123", "p123"
        $patterns = [
            '/\bid[:\s]+(\d+)/i',
            '/#(\d+)/',
            '/product[:\s]+(\d+)/i',
            '/p(\d+)\b/i',
            '/^\s*(\d+)\s*$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $query, $matches)) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    /**
     * Perform a FullText search using NATURAL LANGUAGE MODE.
     *
     * @param Store $store
     * @param string $query
     * @return Product|Collection|null
     */
    private function searchByFullText(Store $store, string $query): Product|Collection|null
    {
        $results = Product::where('store_id', $store->id)
            ->whereRaw("MATCH(name, description) AGAINST(? IN NATURAL LANGUAGE MODE)", [$query])
            ->orderByRaw("MATCH(name, description) AGAINST(? IN NATURAL LANGUAGE MODE) DESC", [$query])
            ->get();

        if ($results->isEmpty()) {
            return null;
        }

        // Return single product if only one match, collection if multiple
        return $results->count() === 1 ? $results->first() : $results;
    }

    /**
     * Perform a FullText search on products table.
     *
     * @param Store $store
     * @param string $query
     * @return Product|Collection|null
     */
    private function fullTextSearch(Store $store, string $query): Product|Collection|null
    {
        // Perform FullText search on name and description
        $results = Product::where('store_id', $store->id)
            ->whereRaw("MATCH(name, description) AGAINST(? IN BOOLEAN MODE)", [$query])
            ->orderByRaw("MATCH(name, description) AGAINST(? IN BOOLEAN MODE) DESC", [$query])
            ->get();

        if ($results->isEmpty()) {
            return null;
        }

        // If only one result, return the model directly
        if ($results->count() === 1) {
            return $results->first();
        }

        // Return collection if multiple matches
        return $results;
    }
}
