<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\MessageBus\Envelope as Envelope;
use Thesis\MessageBus\Handler\Context;

/**
 * @api
 * @template-contravariant TCalls of Call = never
 */
final class Invoker
{
    /**
     * @template TCall of Call
     * @param class-string<TCall> ...$call
     * @return class-string<Invoker<TCall>>
     */
    public static function class(string ...$call): string
    {
        return self::class;
    }

    /**
     * @var list<Envelope<Command>>
     */
    public private(set) array $commands = [];

    /**
     * @var list<Envelope<Event>>
     */
    public private(set) array $events = [];

    /**
     * @param Handler<TCalls> $handler
     */
    public function __construct(
        private readonly Handler $handler,
        private readonly Context $context = new Context(),
    ) {}

    /**
     * @template TResult
     * @param (Call<TResult>&TCalls)|Envelope<Call<TResult>&TCalls> $call
     * @return TResult
     */
    public function invoke(Call|Envelope $call): mixed
    {
        $result = $this->handler->handle(Envelope::wrap($call), $this->context->with($this));

        $this->commands = [...$this->commands, ...$result->commands];
        $this->events = [...$this->events, ...$result->events];

        return $result->result;
    }
}
