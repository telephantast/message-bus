<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Event;

use Thesis\MessageBus\Call;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Invoker;
use Thesis\MessageBus\Result;
use Thesis\MessageBus\Stamps;

/**
 * @template-contravariant TEvent of object
 * @implements EventListener<TEvent>
 */
final readonly class CallableEventListener implements EventListener
{
    /**
     * @param callable(TEvent, Invoker<Call>, Stamps): (void|null|Result<null>) $listener
     */
    public function __construct(
        private mixed $listener,
    ) {}

    public function on(Envelope $event, Invoker $invoker): Result
    {
        $result = ($this->listener)($event->message, $invoker, $event->stamps);

        if ($result instanceof Result) {
            return $result;
        }

        return new Result();
    }
}
