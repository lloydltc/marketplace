<?php

namespace App\Console\Commands;

use App\Modules\Rfq\Models\PartRequest;
use App\Modules\Rfq\Models\Quote;
use Illuminate\Console\Command;

/**
 * Phase 15 §15.3: expire stale part requests and quotes so the board stays fresh.
 */
class ExpireRfqRequests extends Command
{
    protected $signature = 'rfq:expire';

    protected $description = 'Expire part requests and quotes past their expiry date';

    public function handle(): int
    {
        $requests = PartRequest::whereIn('status', ['open', 'quoted'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        $quotes = Quote::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        $this->info("Expired {$requests} request(s) and {$quotes} quote(s).");

        return self::SUCCESS;
    }
}
