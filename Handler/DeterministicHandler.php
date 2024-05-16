<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Handler;

use Telephantast\Message\Message;
use Telephantast\MessageBus\Handler;
use Telephantast\MessageBus\MessageContext;

/**
 * @api
 * @template TResult
 * @template TMessage of Message<TResult>
 * @implements Handler<TResult, TMessage>
 */
final readonly class DeterministicHandler implements Handler
{
    /**
     * @param non-empty-string $id
     * @param TResult $result
     */
    public function __construct(
        private string $id,
        private mixed $result,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function handle(MessageContext $messageContext): mixed
    {
        return $this->result;
    }
}
