<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @template-covariant TResult = mixed
 */
final readonly class Result
{
    /**
     * @param TResult $result
     * @param list<Envelope> $commandEnvelopes
     * @param list<Envelope> $eventEnvelopes
     */
    public function __construct(
        public mixed $result = null,
        public array $commandEnvelopes = [],
        public array $eventEnvelopes = [],
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
            commandEnvelopes: $this->commandEnvelopes,
            eventEnvelopes: $this->eventEnvelopes,
        );
    }

    /**
     * @no-named-arguments
     */
    public function send(object ...$commands): static
    {
        return new self(
            result: $this->result,
            commandEnvelopes: [...$this->commandEnvelopes, ...array_map(Envelope::wrap(...), $commands)],
            eventEnvelopes: $this->eventEnvelopes,
        );
    }

    /**
     * @no-named-arguments
     */
    public function publish(object ...$events): static
    {
        return new self(
            result: $this->result,
            commandEnvelopes: $this->commandEnvelopes,
            eventEnvelopes: [...$this->eventEnvelopes, ...array_map(Envelope::wrap(...), $events)],
        );
    }
}

/**
 * @template TResult
 * @param TResult $result
 * @param list<object> $commands
 * @param list<object> $events
 * @return Result<TResult>
 */
function result(mixed $result = null, array $commands = [], array $events = []): Result
{
    return new Result(
        result: $result,
        commandEnvelopes: array_map(Envelope::wrap(...), $commands),
        eventEnvelopes: array_map(Envelope::wrap(...), $events),
    );
}

/**
 * @no-named-arguments
 * @return Result<null>
 */
function send(object ...$commands): Result
{
    return new Result(commandEnvelopes: array_map(Envelope::wrap(...), $commands));
}

/**
 * @no-named-arguments
 * @return Result<null>
 */
function publish(object ...$events): Result
{
    return new Result(eventEnvelopes: array_map(Envelope::wrap(...), $events));
}
