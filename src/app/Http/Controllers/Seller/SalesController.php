<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * P1: the private seller's "Sales" surface. Private-seller vehicles are lead-gen
 * (BUSINESS_MODEL.md §8) — they never go through checkout, so there are no
 * transactional orders to receive. This surface therefore shows the seller's
 * listings as their shopfront plus an enquiries panel (buyers contact them via
 * the details on each listing). It is scoped to the acting seller only.
 */
class SalesController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $listings = $user->vehicles()
            ->whereNull('deleted_at')
            ->latest()
            ->paginate(10);

        $stats = [
            'active'  => $user->vehicles()->where('status', 'active')->count(),
            'pending' => $user->vehicles()->where('status', 'pending')->count(),
            'total'   => $user->vehicles()->whereNull('deleted_at')->count(),
        ];

        return view('seller.sales.index', compact('listings', 'stats'));
    }
}
