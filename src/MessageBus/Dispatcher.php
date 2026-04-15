<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 */
interface Dispatcher
{
    /**
     * @param non-empty-list<Envelope> $messages
     */
    public function dispatch(array $messages): void;
}
