<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Command;
use Thesis\MessageBus\Event;
use Thesis\MessageBus\HandlerContext;
use Thesis\MessageBus\Reply;

/**
 * @internal
 *
 * @template-covariant Tx of object
 *
 * @implements HandlerContext<Tx>
 */
final class Context implements HandlerContext
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
        public readonly object $transaction,
    ) {}

    public function send(object ...$commands): void
    {
        foreach ($commands as $command) {
            $this->outgoingMessages[] = Command::from($command);
        }
    }

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
