<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Recoverability;

/**
 * @api
 */
enum Action
{
    /**
     * Store the failed message in {@see DeadLetterStorage} for later inspection or manual recovery.
     */
    case Bury;

    /**
     * Acknowledge the failed message without storing it for later recovery.
     */
    case Discard;
}
