<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\EntityHandler;

use Telephantast\MessageBus\Handler\Mapping\HandlerDescriptor;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class FactoryMethod
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        public readonly string $name,
    ) {}

    public function checkValidFor(\ReflectionClass $entityClass, \ReflectionClass $messageClass): void
    {
        $method = $entityClass->getMethod($this->name);
        \assert($method->isStatic());
        $handlerDescriptor = HandlerDescriptor::fromFunction($method);
        \assert(\in_array($messageClass->name, $handlerDescriptor->messageClasses, true));
        $returnType = $method->getReturnType();
        \assert($returnType instanceof \ReflectionNamedType && \in_array($returnType->getName(), ['self', 'static', 'never'], true));
    }
}
