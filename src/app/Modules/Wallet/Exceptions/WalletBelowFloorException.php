<?php

namespace App\Modules\Wallet\Exceptions;

use RuntimeException;

/**
 * Thrown when a vendor whose wallet balance is below the configured floor
 * attempts an action that requires good standing (e.g. creating a listing).
 */
class WalletBelowFloorException extends RuntimeException
{
}
