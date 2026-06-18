<?php

namespace App\Jobs\Mail;

use App\Mail\VendorInvitationMailable;
use App\Models\VendorInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendVendorInvitationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public VendorInvitation $invitation) {}

    public function handle(): void
    {
        Mail::to($this->invitation->email)->send(new VendorInvitationMailable($this->invitation));
    }
}
