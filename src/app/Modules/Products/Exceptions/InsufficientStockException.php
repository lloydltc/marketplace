<?php

namespace App\Modules\Products\Exceptions;

use RuntimeException;

/** PM2: thrown when a stock change would drive an offering's quantity below zero. */
class InsufficientStockException extends RuntimeException
{
    public static function for(string $title, int $available, int $requested): self
    {
        return new self("Insufficient stock for \"{$title}\": {$available} available, {$requested} requested.");
    }
}
