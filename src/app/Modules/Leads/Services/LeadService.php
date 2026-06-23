<?php

namespace App\Modules\Leads\Services;

use App\Models\User;
use App\Modules\Leads\Models\Lead;
use App\Modules\Products\Models\Product;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * D6: central recorder for every contact event. PII (name/phone/message) is
 * persisted to the leads table but NEVER written to application logs.
 */
class LeadService
{
    /**
     * @param  array{
     *   buyer?: ?User, contact_name?: ?string, contact_phone?: ?string,
     *   contact_email?: ?string, message?: ?string, source?: ?string, ip?: ?string
     * }  $meta
     */
    public function record(string $type, ?Model $subject = null, array $meta = []): Lead
    {
        [$sellerUserId, $vendorId] = $this->ownerOf($subject);

        $lead = Lead::create([
            'type'           => $type,
            'subject_type'   => $subject ? $subject::class : null,
            'subject_id'     => $subject?->getKey(),
            'seller_user_id' => $sellerUserId,
            'vendor_id'      => $vendorId,
            'buyer_user_id'  => ($meta['buyer'] ?? null)?->id,
            'contact_name'   => $meta['contact_name'] ?? null,
            'contact_phone'  => $meta['contact_phone'] ?? null,
            'contact_email'  => $meta['contact_email'] ?? null,
            'message'        => $meta['message'] ?? null,
            'source'         => $meta['source'] ?? null,
            'ip_address'     => $meta['ip'] ?? null,
        ]);

        // Log the event WITHOUT any PII — ids and type only.
        Log::info('Lead captured', [
            'lead_id' => $lead->id, 'type' => $type,
            'subject_type' => $lead->subject_type, 'subject_id' => $lead->subject_id,
            'guest' => $lead->isGuest(),
        ]);

        return $lead;
    }

    /** Resolve the (seller_user_id, vendor_id) owner of a listing subject. */
    private function ownerOf(?Model $subject): array
    {
        if ($subject instanceof Vehicle) {
            return [$subject->user_id, $subject->vendor_id];
        }
        if ($subject instanceof Product) {
            return [null, $subject->vendor_id];
        }

        return [null, null];
    }
}
