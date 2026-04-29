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
     * @param non-empty-string $consumer
     * @param Tx $transaction
     */
    public function __construct(
        public readonly string $consumer,
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
            if (!$command instanceof Draft) {
                $command = Draft::command($command);
            }

            $this->outgoing[] = $command->seal($this->consumer, $this->idGenerator, $this->cause);
        }
    }

    /**
     * @no-named-arguments
     */
    public function publish(object ...$events): void
    {
        foreach ($events as $event) {
            if (!$event instanceof Draft) {
                $event = Draft::event($event);
            }

            $this->outgoing[] = $event->seal($this->consumer, $this->idGenerator, $this->cause);
        }
    }
}
