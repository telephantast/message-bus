<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;
use Thesis\MessageBus\Internal\MessageClassesParser;
use function Typhoon\Formatter\formatReflectedFunction;

/**
 * @template-covariant TResult
 * @template TMessage of Message<TResult>
 * @template-covariant TRequiredMessages of Message = \Thesis\Message\Event
 * @template-contravariant TTransaction of object = object
 * @implements Handler<TMessage, TRequiredMessages, TTransaction>
 */
final readonly class CallableHandler implements Handler
{
    public string $id;

    public array $messageClasses;

    /**
     * @param callable(TMessage, HandlerContext<TRequiredMessages, TTransaction>, Stamps): TResult $handler
     * @param ?non-empty-string $id
     */
    public function __construct(
        private mixed $handler,
        ?string $id = null,
    ) {
        $reflection = new \ReflectionFunction($handler(...));
        $this->id = $id ?? formatReflectedFunction($reflection);
        /** @var non-empty-list<class-string<TMessage>> */
        $messageClasses = MessageClassesParser::fromParameter($reflection->getParameters()[0]);
        $this->messageClasses = $messageClasses;
    }

    public function handle(Envelope $envelope, HandlerContext $context): mixed
    {
        /** @phpstan-ignore argument.type, return.type */
        return ($this->handler)($envelope->message, $context, $envelope->stamps);
    }
}
