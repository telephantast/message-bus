<?php

declare(strict_types=1);

namespace Thesis\MessageBus\HandlerRegistry;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Thesis\Message\Message;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\HandlerRegistry;

/**
 * @api
 */
final class PsrContainerHandlerRegistry extends HandlerRegistry
{
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
            $handler = $this->handlers->get($messageClass);
            \assert($handler instanceof Handler);

            /** @var Handler<TResult, TMessage> */
            return $handler;
        } catch (NotFoundExceptionInterface) {
            return null;
        }
    }
}
