<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\HandlerRegistry;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Telephantast\Message\Message;
use Telephantast\MessageBus\Handler;
use Telephantast\MessageBus\HandlerRegistry;

/**
 * @api
 */
final class PsrContainerHandlerRegistry extends HandlerRegistry
{
    /**
     * @param ContainerInterface<Handler> $handlers
     */
    public function __construct(
        private readonly ContainerInterface $handlers,
    ) {}

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param class-string<TMessage> $messageClass
     * @return ?Handler<TResult, TMessage>
     * @throws ContainerExceptionInterface
     */
    public function find(string $messageClass): ?Handler
    {
        try {
            /** @var ?Handler<TResult, TMessage> */
            return $this->handlers->get($messageClass);
        } catch (NotFoundExceptionInterface) {
            return null;
        }
    }
}
