<?php

namespace App\Modules\Vehicles\Repositories;

use App\Modules\Settings\Services\SettingsService;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VehicleRepository implements VehicleRepositoryInterface
{
    public function __construct(
        private readonly Vehicle $model,
        private readonly SettingsService $settings
    ) {}

    public function find(string $id): ?Vehicle
    {
        return $this->model->with(['make', 'vehicleModel', 'vendor', 'seller'])->find($id);
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->query()->with(['make', 'vehicleModel', 'vendor', 'seller']);

        $this->applyCommonFilters($query, $filters);

        if (! empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function paginateForVendor(string $vendorId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->forVendor($vendorId)
            ->with(['make', 'vehicleModel']);

        if (! empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function paginateForSeller(string $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->forSeller($userId)
            ->with(['make', 'vehicleModel']);

        if (! empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function paginatePublic(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->active()
            // D5: hide listings whose expiry has lapsed but the sweep job hasn't run yet.
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->with(['make', 'vehicleModel', 'vendor', 'seller', 'images']);

        $this->applyCommonFilters($query, $filters);

        // Featured vehicle priority — config-weighted (BUSINESS_MODEL.md §8).
        // Active (unexpired) featured listings float to the top by this weight.
        // Set the setting to 0 to disable featured priority entirely.
        $boost = $this->settings->getDecimal('search.featured_vehicle_boost', 0);
        $query->orderByRaw(
            '(CASE WHEN featured_until IS NOT NULL AND featured_until > now() THEN ? ELSE 0 END) DESC',
            [$boost]
        );

        $sort = $filters['sort'] ?? 'latest';

        match ($sort) {
            'price_asc'   => $query->orderBy('price_zwl'),
            'price_desc'  => $query->orderByDesc('price_zwl'),
            'year_desc'   => $query->orderByDesc('year'),
            'year_asc'    => $query->orderBy('year'),
            'mileage_asc' => $query->orderBy('mileage'),
            default       => $query->latest(),
        };

        return $query->paginate($perPage);
    }

    /**
     * Make/model suggestions for the vehicle search autocomplete.
     *
     * @return array<int, string>
     */
    public function suggest(string $term, int $limit = 8): array
    {
        $like = '%' . $term . '%';

        // Suggest "Make Model" pairs that have at least one active listing.
        $models = VehicleMake::query()
            ->where('name', 'ilike', $like)
            ->orWhereHas('models', fn ($q) => $q->where('name', 'ilike', $like))
            ->with(['models' => fn ($q) => $q->where('name', 'ilike', $like)])
            ->orderBy('name')
            ->limit($limit)
            ->get();

        $suggestions = [];
        foreach ($models as $make) {
            if (stripos($make->name, $term) !== false) {
                $suggestions[] = $make->name;
            }
            foreach ($make->models as $model) {
                $suggestions[] = $make->name . ' ' . $model->name;
            }
        }

        return array_values(array_unique(array_slice($suggestions, 0, $limit)));
    }

    public function create(array $data): Vehicle
    {
        return $this->model->create($data);
    }

    public function update(Vehicle $vehicle, array $data): Vehicle
    {
        $vehicle->update($data);

        return $vehicle->refresh();
    }

    public function delete(Vehicle $vehicle): void
    {
        $vehicle->delete();
    }

    private function applyCommonFilters($query, array $filters): void
    {
        // H0/H6: listing type (cars/bikes/boats/trailers).
        if (! empty($filters['vehicle_type'])) {
            $query->where('vehicle_type', $filters['vehicle_type']);
        }

        if (! empty($filters['search'])) {
            $like = '%' . trim($filters['search']) . '%';
            $query->where(function ($q) use ($like, $filters) {
                $q->whereHas('make', fn ($m) => $m->where('name', 'ilike', $like))
                  ->orWhereHas('vehicleModel', fn ($m) => $m->where('name', 'ilike', $like))
                  ->orWhere('description', 'ilike', $like);

                // Allow searching by a 4-digit year.
                if (preg_match('/\b(19|20)\d{2}\b/', $filters['search'], $m)) {
                    $q->orWhere('year', (int) $m[0]);
                }
            });
        }

        if (! empty($filters['make_id'])) {
            $query->where('make_id', $filters['make_id']);
        }

        if (! empty($filters['model_id'])) {
            $query->where('model_id', $filters['model_id']);
        }

        if (! empty($filters['year_min'])) {
            $query->where('year', '>=', $filters['year_min']);
        }

        if (! empty($filters['year_max'])) {
            $query->where('year', '<=', $filters['year_max']);
        }

        if (! empty($filters['mileage_max'])) {
            $query->where('mileage', '<=', $filters['mileage_max']);
        }

        if (! empty($filters['min_price'])) {
            $query->where('price_zwl', '>=', $filters['min_price']);
        }

        if (! empty($filters['max_price'])) {
            $query->where('price_zwl', '<=', $filters['max_price']);
        }

        if (! empty($filters['body_type'])) {
            $query->where('body_type', $filters['body_type']);
        }

        if (! empty($filters['transmission'])) {
            $query->where('transmission', $filters['transmission']);
        }

        if (! empty($filters['fuel_type'])) {
            $query->where('fuel_type', $filters['fuel_type']);
        }

        if (! empty($filters['condition'])) {
            $query->where('condition', $filters['condition']);
        }

        // Dynamic feature facets (D3 + D4). features = [definition_id => value].
        // Each provided facet AND-narrows the results via the indexed value table.
        if (! empty($filters['features']) && is_array($filters['features'])) {
            foreach ($filters['features'] as $definitionId => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $query->whereHas('featureValues', function ($q) use ($definitionId, $value) {
                    $q->where('feature_definition_id', $definitionId)->where('value', (string) $value);
                });
            }
        }
    }
}
