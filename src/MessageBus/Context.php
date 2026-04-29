<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Envelope\Metadata;

/**
 * @api
 *
 * @template-covariant Tx of object = object
 */
final class Context
{
    /**
     * @var list<Envelope>
     */
    public private(set) array $outgoing = [];

    /**
     * @param non-empty-string $endpoint
     * @param Tx $transaction
     */
    public function __construct(
        public readonly string $endpoint,
        public readonly object $transaction,
        private readonly ?Metadata $cause = null,
        private readonly IdGenerator $idGenerator = new IdGenerator\UuidV7(),
    ) {}

    /**
     * @no-named-arguments
     */
    public function send(object ...$commands): void
    {
        foreach ($commands as $command) {
            $this->outgoing[] = match ($command::class) {
                Envelope::class => $command,
                Draft::class => $this->seal($command),
                default => $this->seal(Draft::command($command)),
            };
        }
    }

    /**
     * @no-named-arguments
     */
    public function publish(object ...$events): void
    {
        foreach ($events as $event) {
            $this->outgoing[] = match ($event::class) {
                Envelope::class => $event,
                Draft::class => $this->seal($event),
                default => $this->seal(Draft::event($event)),
            };
        }
    }

    /**
     * @template T of object
     * @param Draft<T>|Envelope<T> $message
     * @return Envelope<T>
     */
    private function seal(Draft|Envelope $message): Envelope
    {
        if ($message instanceof Draft) {
            return $message->seal($this->endpoint, $this->idGenerator, $this->cause);
        }

        return $message;
    }
}
