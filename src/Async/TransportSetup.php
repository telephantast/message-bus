<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async;

use Thesis\Message\Message;

/**
 * @api
 */
interface TransportSetup
{
    /**
     * @param array<class-string<Message>, list<non-empty-string>> $messageClassToQueues
     */
    public function setup(array $messageClassToQueues): void;
}
