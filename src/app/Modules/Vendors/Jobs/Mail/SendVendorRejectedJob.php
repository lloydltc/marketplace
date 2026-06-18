<?php

namespace App\Modules\Vendors\Jobs\Mail;

use App\Mail\VendorRejectedMailable;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendVendorRejectedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly Vendor $vendor,
        public readonly string $reason
    ) {}

    public function handle(): void
    {
        Mail::to($this->vendor->contact_email)
            ->send(new VendorRejectedMailable($this->vendor, $this->reason));
    }
}
