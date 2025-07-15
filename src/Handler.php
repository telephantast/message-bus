<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;

/**
 * @template TMessage of Message
 */
interface Handler
{
    /**
     * @var list<class-string<TMessage>>
     */
    public array $messageClasses { get; }

    /**
     * @template TResult
     * @param Envelope<TMessage&Message<TResult>> $envelope
     * @return TResult
     */
    public function handle(Envelope $envelope, Context $context): mixed;
}
