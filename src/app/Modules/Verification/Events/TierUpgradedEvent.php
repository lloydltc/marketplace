<?php

namespace App\Modules\Verification\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TierUpgradedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $ownerType,  // 'vendor' or 'seller'
        public readonly string $ownerId,
        public readonly string $newTier,
        public readonly string $previousTier,
    ) {}
}
