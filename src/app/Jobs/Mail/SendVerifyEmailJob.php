<?php

namespace App\Jobs\Mail;

use App\Mail\VerifyEmailMailable;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendVerifyEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public User $user, public string $otp) {}

    public function handle(): void
    {
        Mail::to($this->user->email)->send(new VerifyEmailMailable($this->user, $this->otp));
    }
}
