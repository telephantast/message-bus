<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling\Internal;

use Thesis\Headers;
use Thesis\MessageBus\HandlerContext;
use Thesis\MessageBus\Handling\HandlerRegistry;
use Thesis\MessageBus\Handling\NoHandler;
use Thesis\MessageBus\Metadata\Internal\MessageMetadataFactory;
use Thesis\MessageBus\Metadata\MessageKind;
use Thesis\MessageBus\Protocol\RoutedCorrelationId;
use const Thesis\MessageBus\Protocol\CORRELATION_ID;

/**
 * @internal
 *
 * @template Tx of object
 */
final readonly class HandlerRouter
{
    /**
     * @param HandlerRegistry<Tx> $handlerRegistry
     */
    public function __construct(
        private HandlerRegistry $handlerRegistry,
        private MessageMetadataFactory $messageMetadataFactory,
    ) {}

    /**
     * @template T of object
     * @param class-string<T> $messageClass
     * @return callable(T, HandlerContext, Tx): void
     */
    public function handlerFor(string $messageClass, Headers $headers): callable
    {
        $handlers = $this->handlerRegistry->findHandlers(
            messageClass: $messageClass,
            qualifier: $this->resolveHandlerQualifier($messageClass, $headers),
        );

        if ($handlers === []) {
            throw new NoHandler($messageClass);
        }

        if (\count($handlers) > 1) {
            // todo exception
            throw new \LogicException();
        }

        return $handlers[0];
    }

    /**
     * @param class-string $messageClass
     * @return ?non-empty-string
     */
    private function resolveHandlerQualifier(string $messageClass, Headers $headers): ?string
    {
        if ($this->messageMetadataFactory->forClass($messageClass)->kind !== MessageKind::Reply) {
            return null;
        }

        $correlationId = $headers->find(CORRELATION_ID);

        if ($correlationId instanceof RoutedCorrelationId) {
            return $correlationId->handlerQualifier;
        }

        return null;
    }
}
