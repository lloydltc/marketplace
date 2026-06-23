<?php

namespace App\Http\Controllers;

use App\Modules\Leads\Services\LeadService;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * D6: records a buyer→seller contact event and reveals the seller's contact
 * details. Public + guest-friendly (vehicles are lead-gen); rate-limited.
 */
class ListingContactController extends Controller
{
    public function __construct(private readonly LeadService $leads) {}

    public function vehicle(Request $request, Vehicle $vehicle): JsonResponse
    {
        abort_unless($vehicle->isActive(), 404);

        $validated = $request->validate([
            'type'    => ['nullable', 'in:contact_reveal,call_click,whatsapp_click,message'],
            'name'    => ['nullable', 'string', 'max:120'],
            'phone'   => ['nullable', 'string', 'max:30'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $type = $validated['type'] ?? 'contact_reveal';

        $this->leads->record($type, $vehicle, [
            'buyer'         => $request->user(),
            'contact_name'  => $validated['name'] ?? $request->user()?->name,
            'contact_phone' => $validated['phone'] ?? null,
            'message'       => $validated['message'] ?? null,
            'source'        => $request->headers->get('referer'),
            'ip'            => $request->ip(),
        ]);

        // H5: mirror the contact as an analytics event (deduped/bot-filtered).
        $analyticsType = $type === 'contact_reveal' ? 'phone_reveal' : ($type === 'message' ? 'enquiry' : $type);
        app(\App\Modules\Analytics\Services\AnalyticsService::class)->record($analyticsType, $vehicle, $request);

        $contact = $vehicle->contactDetails();

        return response()->json([
            'ok'      => true,
            'contact' => [
                'name'  => $contact['name'],
                'phone' => $contact['phone'],
                'email' => $contact['email'],
            ],
        ]);
    }
}
