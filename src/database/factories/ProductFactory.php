<?php

namespace Database\Factories;

use App\Modules\Products\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'id'           => (string) Str::uuid(),
            'title'        => fake()->sentence(5),
            'description'  => fake()->paragraphs(2, true),
            'sku'          => fake()->optional(0.6)->regexify('[A-Z]{2,4}-[0-9]{4,6}'),
            'price_zwl'    => fake()->randomFloat(2, 50, 50000),
            'price_usd'    => fake()->optional(0.4)->randomFloat(2, 1, 500),
            'quantity'     => fake()->numberBetween(0, 200),
            'status'       => 'pending',
            'rating'       => 0,
            'review_count' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function rejected(): static
    {
        return $this->state(['status' => 'rejected']);
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }

    public function outOfStock(): static
    {
        return $this->state(['quantity' => 0]);
    }

    public function withSku(string $sku): static
    {
        return $this->state(['sku' => $sku]);
    }
}
