<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\IdGenerator\Random;

/**
 * @api
 */
final readonly class Dispatch
{
    /**
     * @param list<self> $dispatches
     */
    public static function merge(array $dispatches): self
    {
        return new self(array_merge(...array_column($dispatches, 'drafts')));
    }

    /**
     * @param list<Draft> $drafts
     */
    public function __construct(
        public array $drafts = [],
    ) {}

    /**
     * @no-named-arguments
     */
    public function send(object ...$commands): static
    {
        return new self([
            ...$this->drafts,
            ...array_map(Draft::command(...), $commands),
        ]);
    }

    /**
     * @no-named-arguments
     */
    public function publish(object ...$events): static
    {
        return new self([
            ...$this->drafts,
            ...array_map(Draft::event(...), $events),
        ]);
    }

    /**
     * @param non-empty-string $source
     * @param null|non-empty-string|Envelope $cause
     * @return list<Envelope>
     */
    public function seal(string $source, IdGenerator $idGenerator = new Random(), null|string|Envelope $cause = null): array
    {
        return array_map(
            static fn(Draft $draft) => $draft->seal(
                source: $source,
                idGenerator: $idGenerator,
                cause: $cause,
            ),
            $this->drafts,
        );
    }
}

const done = new Dispatch();

/**
 * @no-named-arguments
 */
function send(object ...$commands): Dispatch
{
    return new Dispatch(array_map(command(...), $commands));
}

/**
 * @no-named-arguments
 */
function publish(object ...$events): Dispatch
{
    return new Dispatch(array_map(event(...), $events));
}
