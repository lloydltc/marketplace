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

        $this->leads->record($validated['type'] ?? 'contact_reveal', $vehicle, [
            'buyer'         => $request->user(),
            'contact_name'  => $validated['name'] ?? $request->user()?->name,
            'contact_phone' => $validated['phone'] ?? null,
            'message'       => $validated['message'] ?? null,
            'source'        => $request->headers->get('referer'),
            'ip'            => $request->ip(),
        ]);

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
