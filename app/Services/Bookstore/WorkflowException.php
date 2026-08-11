<?php

namespace App\Services\Bookstore;

use RuntimeException;

/**
 * A workflow rule was broken — an illegal transition, a missing permission, or
 * an attempt to have one person sign two segregated stages.
 *
 * Rendered as a 422 with the message shown to the user, so the UI never has to
 * guess why a button was refused.
 */
class WorkflowException extends RuntimeException
{
}
