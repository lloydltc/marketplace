<?php

namespace App\Modules\Rfq\Exceptions;

use RuntimeException;

/**
 * Thrown when a fair-use threshold blocks an RFQ action (e.g. the monthly free
 * quota is exhausted). Only fires when rfq.thresholds_enabled is on.
 */
class RfqThresholdException extends RuntimeException
{
}
