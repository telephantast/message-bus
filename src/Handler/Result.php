<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\MessageBus\Envelope;

/**
 * @template-covariant TResult
 */
final readonly class Result
{
    /**
     * @param TResult $result
     * @param list<Envelope> $commands
     * @param list<Envelope> $events
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
     */
    public function send(object ...$commands): static
    {
        return new self(
            result: $this->result,
            commands: [...$this->commands, ...array_map(Envelope::wrap(...), $commands)],
            events: $this->events,
        );
    }

    /**
     * @no-named-arguments
     */
    public function publish(object ...$events): static
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

const done = new Result();

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
 * @return Result<null>
 */
function send(object ...$commands): Result
{
    return new Result(commands: array_map(Envelope::wrap(...), $commands));
}

/**
 * @no-named-arguments
 * @return Result<null>
 */
function publish(object ...$events): Result
{
    return new Result(events: array_map(Envelope::wrap(...), $events));
}
