<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;
use Thesis\MessageBus\Handler\Context;

/**
 * @template-contravariant TMessage of Message
 */
interface Handler
{
    /**
     * @template TResult
     * @param Envelope<TMessage&Message<TResult>> $envelope
     * @return Result<TResult>
     */
    public function handle(Envelope $envelope, Context $context): Result;
}
