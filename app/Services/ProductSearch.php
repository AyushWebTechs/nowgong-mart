<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Catalogue search: multi-word matching, Hindi/common synonyms, simple plural stemming,
 * relevance ordering, and typo correction ("did you mean") against the catalogue vocabulary.
 */
class ProductSearch
{
    /** Shopper words → words that appear in product/category names. */
    private const SYNONYMS = [
        'atta' => ['flour', 'wheat'], 'flour' => ['atta'], 'wheat' => ['atta'],
        'chawal' => ['rice'], 'dal' => ['dal', 'lentil'], 'daal' => ['dal'],
        'cheeni' => ['sugar'], 'chini' => ['sugar'], 'namak' => ['salt'],
        'doodh' => ['milk'], 'dudh' => ['milk'], 'dahi' => ['curd'], 'yogurt' => ['curd'],
        'anda' => ['egg'], 'ande' => ['egg'], 'chai' => ['tea'], 'coffee' => ['nescafe'],
        'tel' => ['oil'], 'biscuit' => ['biscuit', 'parle'], 'cookie' => ['biscuit'],
        'chips' => ['lays', 'chips'], 'namkeen' => ['bhujia', 'snack'], 'chocolate' => ['chocolate', 'dairy milk'],
        'coke' => ['coca-cola'], 'cola' => ['coca-cola'], 'soda' => ['coca-cola'], 'juice' => ['frooti', 'mango'],
        'icecream' => ['ice cream'], 'kulfi' => ['kulfi'], 'sabun' => ['soap'],
        'detergent' => ['surf', 'wash'], 'washing' => ['surf'], 'dishwash' => ['vim'], 'toilet' => ['harpic'],
    ];

    /**
     * Restrict and order a product query by relevance to $term. Returns the query unchanged for a blank term.
     */
    public function apply(Builder $query, string $term): Builder
    {
        $tokens = $this->tokens($term);

        if ($tokens === []) {
            return $query;
        }

        foreach ($tokens as $token) {
            $variants = $this->variants($token);

            $query->where(function (Builder $q) use ($variants, $term) {
                foreach ($variants as $v) {
                    $like = '%'.$v.'%';
                    $q->orWhereRaw('LOWER(products.name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(products.description) LIKE ?', [$like])
                        ->orWhereHas('category', fn (Builder $c) => $c->whereRaw('LOWER(name) LIKE ?', [$like]));
                }
                $q->orWhere('products.sku', $term);
            });
        }

        $phrase = mb_strtolower(trim($term));

        return $query->orderByRaw(
            'CASE WHEN LOWER(products.name) LIKE ? THEN 0 WHEN LOWER(products.name) LIKE ? THEN 1 WHEN LOWER(products.name) LIKE ? THEN 2 ELSE 3 END',
            [$phrase.'%', '% '.$phrase.'%', '%'.$phrase.'%']
        );
    }

    /**
     * Search with typo fallback.
     *
     * @return array{term: string, corrected: ?string}
     */
    public function resolve(string $term): array
    {
        $term = trim($term);

        if ($term === '' || $this->apply(Product::active(), $term)->exists()) {
            return ['term' => $term, 'corrected' => null];
        }

        $corrected = $this->correct($term);

        return $corrected && $corrected !== mb_strtolower($term)
            ? ['term' => $corrected, 'corrected' => $corrected]
            : ['term' => $term, 'corrected' => null];
    }

    /**
     * @return array{products: Collection<int, Product>, categories: Collection<int, Category>, corrected: ?string}
     */
    public function suggest(string $term, int $limit = 6): array
    {
        ['term' => $term, 'corrected' => $corrected] = $this->resolve($term);

        $products = $this->apply(Product::active()->with('category'), $term)
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->take($limit)
            ->get();

        $categories = Category::active()
            ->where(function (Builder $q) use ($term) {
                foreach ($this->tokens($term) as $token) {
                    foreach ($this->variants($token) as $v) {
                        $q->orWhereRaw('LOWER(name) LIKE ?', ['%'.$v.'%']);
                    }
                }
            })
            ->ordered()
            ->take(3)
            ->get();

        return ['products' => $products, 'categories' => $categories, 'corrected' => $corrected];
    }

    /** Closest catalogue words for each token, or null when nothing is close enough. */
    public function correct(string $term): ?string
    {
        $vocabulary = $this->vocabulary();
        $changed = false;

        $words = array_map(function (string $token) use ($vocabulary, &$changed) {
            if (in_array($token, $vocabulary, true) || isset(self::SYNONYMS[$token])) {
                return $token;
            }

            $best = null;
            $bestDistance = PHP_INT_MAX;

            foreach ($vocabulary as $word) {
                if (abs(strlen($word) - strlen($token)) > 2) {
                    continue;
                }
                $distance = levenshtein($token, $word);
                if ($distance < $bestDistance) {
                    [$best, $bestDistance] = [$word, $distance];
                }
            }

            $allowed = strlen($token) <= 4 ? 1 : 2;

            if ($best !== null && $bestDistance <= $allowed) {
                $changed = true;

                return $best;
            }

            return $token;
        }, $this->tokens($term));

        return $changed ? implode(' ', $words) : null;
    }

    /** @return list<string> */
    private function tokens(string $term): array
    {
        $words = preg_split('/[^\p{L}\p{N}\-]+/u', mb_strtolower($term), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique($words));
    }

    /** @return list<string> */
    private function variants(string $token): array
    {
        $variants = [$token];

        // Plural → singular so "eggs"/"biscuits" also match "egg"/"biscuit".
        if (strlen($token) > 3 && str_ends_with($token, 's')) {
            $variants[] = substr($token, 0, -1);
        }

        foreach (self::SYNONYMS[$token] ?? self::SYNONYMS[end($variants)] ?? [] as $synonym) {
            $variants[] = $synonym;
        }

        return array_values(array_unique($variants));
    }

    /** @return list<string> */
    private function vocabulary(): array
    {
        $version = Product::max('updated_at').'|'.Category::max('updated_at');

        return Cache::remember('search:vocab:'.md5($version), now()->addHour(), function () {
            $text = Product::active()->pluck('name')->merge(Category::active()->pluck('name'))->implode(' ');

            return collect($this->tokens($text))
                ->filter(fn ($w) => strlen($w) >= 3 && ! is_numeric($w))
                ->merge(array_keys(self::SYNONYMS))
                ->unique()
                ->values()
                ->all();
        });
    }
}
