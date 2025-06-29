<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\Result;

/**
 * @api
 * @template TMessage of Message
 * @implements Handler<TMessage>
 */
final class WithMiddleware implements Handler
{
    /**
     * @param Handler<TMessage> $handler
     * @param list<Middleware>|\Traversable<Middleware> $middleware
     */
    public function __construct(
        private readonly Handler $handler,
        private iterable $middleware,
    ) {}

    /**
     * @var list<class-string<TMessage>>
     */
    public array $messageClasses { get => $this->handler->messageClasses; }

    public function handle(Envelope $envelope, Context $context): Result
    {
        if ($this->middleware instanceof \Traversable) {
            $this->middleware = iterator_to_array($this->middleware, preserve_keys: false);
        }

        if ($this->middleware === []) {
            return $this->handler->handle($envelope, $context);
        }

        /** @phpstan-ignore return.type */
        return new Pipeline(
            handler: $this->handler, /** @phpstan-ignore argument.type */
            middleware: $this->middleware,
            envelope: $envelope,
            context: $context,
        )->continue();
    }
}
