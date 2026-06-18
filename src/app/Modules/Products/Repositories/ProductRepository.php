<?php

namespace App\Modules\Products\Repositories;

use App\Modules\Products\Models\Product;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private readonly Product $model,
        private readonly SettingsService $settings
    ) {}

    public function find(string $id): ?Product
    {
        return $this->model->with(['vendor', 'category'])->find($id);
    }

    public function findForVendor(string $id, string $vendorId): ?Product
    {
        return $this->model
            ->where('vendor_id', $vendorId)
            ->with(['category'])
            ->find($id);
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->query()->with(['vendor', 'category']);

        if (! empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (! empty($filters['vendor_id'])) {
            $query->forVendor($filters['vendor_id']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', $search)
                  ->orWhere('sku', 'ilike', $search);
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function paginateForVendor(string $vendorId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->forVendor($vendorId)
            ->with(['category']);

        if (! empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', $search)
                  ->orWhere('sku', 'ilike', $search);
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function paginatePublic(array $filters = [], int $perPage = 24): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->active()
            ->inStock()
            ->with(['vendor', 'category']);

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['vendor_id'])) {
            $query->forVendor($filters['vendor_id']);
        }

        if (! empty($filters['min_price'])) {
            $query->where('price_zwl', '>=', $filters['min_price']);
        }

        if (! empty($filters['max_price'])) {
            $query->where('price_zwl', '<=', $filters['max_price']);
        }

        if (! empty($filters['fulfilment']) && in_array($filters['fulfilment'], ['fbs', 'vendor'], true)) {
            // Match products that support the requested track (incl. "both").
            $query->whereIn('fulfilment_type', [$filters['fulfilment'], 'both']);
        }

        // FBS placement boost — config-weighted (BUSINESS_MODEL.md §3). Set the
        // setting to 0 to disable the boost entirely.
        $boost = $this->settings->getDecimal('search.fbs_placement_boost', 0);

        if (! empty($filters['search'])) {
            // Full-text search via tsvector, with the FBS boost folded into rank.
            $tsQuery = $this->toTsQuery($filters['search']);

            if ($tsQuery) {
                $query->whereRaw('search_vector @@ to_tsquery(\'english\', ?)', [$tsQuery])
                    ->orderByRaw(
                        'ts_rank(search_vector, to_tsquery(\'english\', ?)) '
                        . "+ (CASE WHEN fulfilment_type IN ('fbs','both') THEN ? ELSE 0 END) DESC",
                        [$tsQuery, $boost]
                    );
            }
        } else {
            $sort = $filters['sort'] ?? 'latest';

            match ($sort) {
                // Explicit user sorts override the placement boost.
                'price_asc'  => $query->orderBy('price_zwl'),
                'price_desc' => $query->orderByDesc('price_zwl'),
                'rating'     => $query->orderByDesc('rating'),
                // Default relevance: FBS-eligible products float up by the boost weight.
                default      => $query->orderByRaw(
                    "(CASE WHEN fulfilment_type IN ('fbs','both') THEN ? ELSE 0 END) DESC, created_at DESC",
                    [$boost]
                ),
            };
        }

        return $query->paginate($perPage);
    }

    /**
     * Build a prefix-matching tsquery string from free-text input.
     */
    private function toTsQuery(string $search): string
    {
        return implode(' & ', array_map(
            fn ($word) => preg_replace('/[^a-zA-Z0-9]/', '', $word) . ':*',
            array_filter(explode(' ', trim($search)))
        ));
    }

    /**
     * Lightweight title/SKU suggestions for the search autocomplete.
     *
     * @return array<int, string>
     */
    public function suggest(string $term, int $limit = 8): array
    {
        $like = '%' . $term . '%';

        return $this->model->query()
            ->active()
            ->inStock()
            ->where('title', 'ilike', $like)
            ->orderByDesc('rating')
            ->limit($limit)
            ->pluck('title')
            ->unique()
            ->values()
            ->all();
    }

    public function create(array $data): Product
    {
        return $this->model->create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->refresh();
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
