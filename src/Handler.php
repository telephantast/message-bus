<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;

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
     * @return TResult
     */
    public function handle(MessageContext $messageContext): mixed;
}
