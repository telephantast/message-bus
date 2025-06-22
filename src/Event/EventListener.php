<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Event;

use Thesis\MessageBus\Call;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Invoker;
use Thesis\MessageBus\Result;

/**
 * @template-contravariant TEvent of object
 */
interface EventListener
{
    /**
     * @param Envelope<TEvent> $event
     * @param Invoker<Call> $invoker
     * @return Result<null>
     */
    public function on(Envelope $event, Invoker $invoker): Result;
}
