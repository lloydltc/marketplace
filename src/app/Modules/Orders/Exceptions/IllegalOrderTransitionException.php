<?php

namespace App\Modules\Orders\Exceptions;

use RuntimeException;

/**
 * Thrown when an order is asked to move to a status that the state machine
 * does not permit from its current status / fulfilment track.
 */
class IllegalOrderTransitionException extends RuntimeException
{
}
