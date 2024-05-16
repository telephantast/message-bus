<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

use Telephantast\Message\Message;

/**
 * @api
 */
interface TransportSetup
{
    /**
     * @param array<non-empty-string, list<class-string<Message>>> $queueToMessageClassesMap
     */
    public function setup(array $queueToMessageClassesMap): void;
}
