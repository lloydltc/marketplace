<?php

namespace App\Modules\Commerce\Services;

use App\Models\Vendor;
use App\Modules\Categories\Models\Category;

/**
 * Computes the commission to snapshot onto an order. The per-LINE resolution
 * (vendor → category → platform default, via {@see CommissionRateResolver})
 * lets a single order mix categories with different rates; the order stores the
 * total commission plus the blended effective rate.
 *
 * Commission applies to the goods subtotal only — never to delivery fees.
 */
class CommissionCalculator
{
    public function __construct(private readonly CommissionRateResolver $resolver) {}

    /**
     * @param  array<int, array{line_total: float|int, category?: ?Category}>  $lines
     * @return array{rate: float, amount: float, net: float, subtotal: float}
     */
    public function forLines(Vendor $vendor, array $lines): array
    {
        $subtotal   = 0.0;
        $commission = 0.0;

        foreach ($lines as $line) {
            $lineTotal  = (float) $line['line_total'];
            $rate       = $this->resolver->resolve($vendor, $line['category'] ?? null);
            $subtotal  += $lineTotal;
            $commission += $lineTotal * $rate / 100;
        }

        $commission    = round($commission, 2);
        $subtotal      = round($subtotal, 2);
        $effectiveRate = $subtotal > 0 ? round($commission / $subtotal * 100, 2) : 0.0;

        return [
            'rate'     => $effectiveRate,
            'amount'   => $commission,
            'net'      => round($subtotal - $commission, 2),
            'subtotal' => $subtotal,
        ];
    }
}
