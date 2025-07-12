<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Command;
use Thesis\Message\Event;

/**
 * @template-covariant TResult = mixed
 */
final readonly class Result
{
    /**
     * @param TResult $result
     * @param list<Envelope<Command>> $commands
     * @param list<Envelope<Event>> $events
     */
    public function __construct(
        public mixed $result = null,
        public array $commands = [],
        public array $events = [],
    ) {}

    /**
     * @template TNewResult
     * @param TNewResult $result
     * @return self<TNewResult>
     */
    public function result(mixed $result): self
    {
        return new self(
            result: $result,
            commands: $this->commands,
            events: $this->events,
        );
    }

    /**
     * @no-named-arguments
     * @param Command|Envelope<Command> ...$commands
     */
    public function commands(Command|Envelope ...$commands): static
    {
        return new self(
            result: $this->result,
            commands: [...$this->commands, ...array_map(Envelope::wrap(...), $commands)],
            events: $this->events,
        );
    }

    /**
     * @no-named-arguments
     * @param Event|Envelope<Event> ...$events
     */
    public function events(Event|Envelope ...$events): static
    {
        return new self(
            result: $this->result,
            commands: $this->commands,
            events: [...$this->events, ...array_map(Envelope::wrap(...), $events)],
        );
    }

    /**
     * @template TNewResult
     * @param Result<TNewResult> $result
     * @return self<TNewResult>
     */
    public function merge(self $result): self
    {
        return new self(
            result: $result->result,
            commands: [...$this->commands, ...$result->commands],
            events: [...$this->events, ...$result->events],
        );
    }
}

/**
 * @template TResult
 * @param TResult $result
 * @return Result<TResult>
 */
function result(mixed $result = null): Result
{
    return new Result($result);
}

/**
 * @no-named-arguments
 * @param Command|Envelope<Command> ...$commands
 * @return Result<null>
 */
function commands(Command|Envelope ...$commands): Result
{
    return new Result(commands: array_map(Envelope::wrap(...), $commands));
}

/**
 * @no-named-arguments
 * @param Event|Envelope<Event> ...$events
 * @return Result<null>
 */
function events(Event|Envelope ...$events): Result
{
    return new Result(events: array_map(Envelope::wrap(...), $events));
}
