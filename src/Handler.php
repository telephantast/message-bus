<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;

/**
 * @api
 * @template TResult = mixed
 * @template TMessage of Message<TResult> = Message<mixed>
 */
interface Handler
{
    /**
     * @return non-empty-string
     */
    public function id(): string;

    /**
     * @param Context<TResult, TMessage> $context
     * @return TResult
     */
    public function handle(Context $context): mixed;
}
