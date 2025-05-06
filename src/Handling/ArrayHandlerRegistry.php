<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling;

use Thesis\Message\Message;
use Thesis\MessageBus\Persistence\Transaction;

/**
 * @api
 * @template-covariant TRegistryMessage of Message = Message
 * @template TTransaction of Transaction = Transaction
 * @implements HandlerRegistry<TTransaction>
 */
final class ArrayHandlerRegistry implements HandlerRegistry
{
    /**
     * @var array<class-string<TRegistryMessage>, list<Handler<TRegistryMessage, TTransaction>>>
     */
    private array $handlersByMessageClass;

    /**
     * @param array<class-string<TRegistryMessage>, Handler<TRegistryMessage, TTransaction>|list<Handler<TRegistryMessage, TTransaction>>> $handlersByMessageClass
     */
    public function __construct(array $handlersByMessageClass = [])
    {
        $this->handlersByMessageClass = array_map(
            static fn(Handler|array $handler): array => \is_array($handler) ? $handler : [$handler],
            $handlersByMessageClass,
        );
    }

    public array $messages {
        get => array_keys($this->handlersByMessageClass);
    }

    /**
     * @template TMessage of Message
     * @param class-string<TMessage> $messageClass
     * @return list<Handler<TMessage, TTransaction>>
     */
    public function getHandlers(string $messageClass): array
    {
        /** @var list<Handler<TMessage, TTransaction>> */
        return $this->handlersByMessageClass[$messageClass] ?? [];
    }
}
