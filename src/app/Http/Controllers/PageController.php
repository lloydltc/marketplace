<?php

namespace App\Http\Controllers;

use App\Modules\Settings\Services\SettingsService;
use Illuminate\View\View;

/**
 * P9: public content / legal pages. The fee schedule is rendered from
 * platform_settings (single source of truth) so published fees can never drift
 * from what the engine actually charges.
 */
class PageController extends Controller
{
    public function terms(): View
    {
        return view('pages.terms');
    }

    public function privacy(): View
    {
        return view('pages.privacy');
    }

    public function codPolicy(): View
    {
        return view('pages.cod-policy');
    }

    public function howFbsWorks(): View
    {
        return view('pages.how-fbs-works');
    }

    public function rfqGuide(): View
    {
        return view('pages.rfq-guide');
    }

    public function fees(SettingsService $settings): View
    {
        $fees = [
            'commission_rate'      => $settings->getDecimal('commission.default_rate', 10),
            'delivery_fbs'         => $settings->getDecimal('delivery.fbs_default_fee', 0),
            'concierge_min'        => $settings->getDecimal('concierge.fee_minimum', 5),
            'concierge_percent'    => $settings->getDecimal('concierge.fee_percent', 10),
            'rfq_free_quota'       => $settings->getInt('rfq.free_quota_monthly', 3),
            'rfq_overage'          => $settings->getDecimal('rfq.overage_fee', 1),
            'featured_vehicle_fee' => $settings->getDecimal('promotion.featured_vehicle_fee', 10),
            'listing_bump_fee'     => $settings->getDecimal('promotion.listing_bump_fee', 2),
        ];

        return view('pages.fees', compact('fees'));
    }
}
