<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Event;
use Thesis\Message\Message;
use Thesis\MessageBus\Handling\Mapping\MessageClassesParser;
use function Typhoon\Formatter\formatReflectedFunction;

/**
 * @template-covariant TResult = null
 * @template TMessage of Message<TResult> = Message<null>
 * @template-covariant TRequiredMessages of Message = Event
 * @template-contravariant TTransaction of object = object
 * @implements Handler<TResult, TMessage, TRequiredMessages, TTransaction>
 */
final readonly class CallableHandler implements Handler
{
    public string $id;

    public array $messageClasses;

    /**
     * @param callable(TMessage, HandlerContext<TRequiredMessages, TTransaction>): TResult $handler
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

    public function handle(Message $message, HandlerContext $context): mixed
    {
        return ($this->handler)($message, $context);
    }
}
