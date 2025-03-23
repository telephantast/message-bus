<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Authorization;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Telephantast\Message\Message;

/**
 * @api
 * @template TPassport of object
 */
final class AuthorizerRegistry
{
    /**
     * @param ContainerInterface<callable(Message, ?TPassport): (bool|Message)> $authorizersByMessageClass
     */
    public function __construct(
        private readonly ContainerInterface $authorizersByMessageClass,
    ) {}

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param class-string<TMessage> $messageClass
     * @return ?callable(Message, ?TPassport): (bool|Message)
     */
    public function get(string $messageClass): ?callable
    {
        try {
            /** @var callable(Message, ?TPassport): bool */
            return $this->authorizersByMessageClass->get($messageClass);
        } catch (NotFoundExceptionInterface) {
            return null;
        }
    }
}
