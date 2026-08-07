<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption\Recoverability;

/**
 * @api
 */
enum Action
{
    /**
     * Send the failed message to the configured dead-letter queue for later inspection or manual recovery.
     */
    case Bury;

    /**
     * Acknowledge the failed message without storing it for later recovery.
     */
    case Discard;
}
