<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;
use Thesis\MessageBus\Handler\Context;

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
     * @param non-empty-string $endpoint
     * @param Envelope<TMessage&Message<TResult>> $envelope
     * @return TResult
     */
    public function handle(string $endpoint, Envelope $envelope, Context $context): mixed;
}
