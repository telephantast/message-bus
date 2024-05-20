<?php

declare(strict_types=1);

namespace Telephantast\MessageBus;

use Telephantast\Message\Message;

/**
 * @api
 * @template TResult
 * @template TMessage of Message<TResult>
 */
interface Handler
{
    /**
     * @return non-empty-string
     */
    public function id(): string;

    /**
     * @param MessageContext<TResult, TMessage> $messageContext
     * @return (TResult is void ? null : TResult)
     */
    public function handle(MessageContext $messageContext): mixed;
}
