<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 *
 * @template-covariant Tx of object
 */
final class HandlerContext
{
    /**
     * @param non-empty-string $endpoint
     * @param Tx $transaction
     */
    public function __construct(
        public readonly string $endpoint,
        public readonly object $transaction,
        public readonly bool $canReply = false,
    ) {}

    /**
     * @var list<Command|Event|Reply>
     */
    public private(set) array $outgoingMessages = [];

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

    public private(set) bool $replied = false;

    public function reply(object $reply): void
    {
        if (!$this->canReply) {
            throw new \LogicException('Cannot reply to this message');
        }

        if ($this->replied) {
            throw new \LogicException('Cannot reply more than once');
        }

        $this->replied = true;
        $this->outgoingMessages[] = Reply::from($reply);
    }
}
