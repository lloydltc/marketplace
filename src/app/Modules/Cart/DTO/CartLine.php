<?php

namespace App\Modules\Cart\DTO;

use App\Modules\Products\Models\Product;

final class CartLine
{
    public function __construct(
        public readonly Product $product,
        public readonly int $quantity,
    ) {}

    public function lineTotal(): float
    {
        return (float) $this->product->price_zwl * $this->quantity;
    }
}
