<?php

namespace App\Jobs\Mail;

use App\Mail\AccountSuspendedMailable;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendAccountSuspendedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public User $user,
        public string $reason = 'Your account has been suspended due to a policy violation.'
    ) {}

    public function handle(): void
    {
        Mail::to($this->user->email)->send(new AccountSuspendedMailable($this->user, $this->reason));
    }
}
