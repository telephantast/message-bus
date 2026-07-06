<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 *
 * @template-covariant Tx of object = object
 */
final class Context
{
    /**
     * @var list<Command|Event|Reply>
     */
    public private(set) array $outgoingMessages = [];

    /**
     * @param non-empty-string $endpoint
     * @param Tx $transaction
     */
    public function __construct(
        public readonly string $endpoint,
        public readonly Metadata $metadata,
        public readonly object $transaction,
    ) {}

    /**
     * @no-named-arguments
     */
    public function send(object ...$commands): void
    {
        foreach ($commands as $command) {
            $this->outgoingMessages[] = Command::from($command);
        }
    }

    /**
     * @no-named-arguments
     */
    public function publish(object ...$events): void
    {
        foreach ($events as $event) {
            $this->outgoingMessages[] = Event::from($event);
        }
    }

    public function reply(object $reply): void
    {
        $this->outgoingMessages[] = Reply::from($reply);
    }
}
