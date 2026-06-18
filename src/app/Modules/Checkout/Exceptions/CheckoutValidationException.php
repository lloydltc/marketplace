<?php

namespace App\Modules\Checkout\Exceptions;

use RuntimeException;

/**
 * Thrown when a submitted checkout fails server-side validation — most
 * importantly when a buyer attempts an ineligible COD/fulfilment combination
 * (BUSINESS_MODEL.md §3). The message is safe to surface to the buyer.
 */
class CheckoutValidationException extends RuntimeException
{
}
