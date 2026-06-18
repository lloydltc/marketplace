<?php

namespace App\Modules\Products\Events;

use App\Modules\Products\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductRejectedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Product $product,
        public readonly string $reason
    ) {}
}
