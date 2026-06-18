<?php

namespace App\Jobs\Mail;

use App\Mail\PasswordResetMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPasswordResetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public string $resetUrl,
        public string $email
    ) {}

    public function handle(): void
    {
        Mail::to($this->email)->send(new PasswordResetMailable($this->resetUrl, $this->email));
    }
}
