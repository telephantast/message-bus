<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\EntityHandler;

use Telephantast\Message\Message;
use Telephantast\MessageBus\Handler;
use Telephantast\MessageBus\MessageContext;

/**
 * @api
 * @template TResult
 * @template TMessage of Message<TResult>
 * @template TEntity of object
 * @implements Handler<TResult, TMessage>
 */
final class EntityHandler implements Handler
{
    /**
     * @param non-empty-string $id
     * @param class-string<TEntity> $class
     * @param ?non-empty-string $factoryMethod
     * @param non-empty-string $handlerMethod
     */
    public function __construct(
        private readonly string $id,
        private readonly string $class,
        private readonly EntityFinder $finder,
        private readonly FindBy $findBy,
        private readonly ?string $factoryMethod,
        private readonly string $handlerMethod,
        private readonly EntitySaver $saver,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function handle(MessageContext $messageContext): mixed
    {
        $message = $messageContext->getMessage();
        $entity = $this->finder->findBy($this->class, $this->findBy->resolve($message));

        if ($entity === null) {
            if ($this->factoryMethod === null) {
                throw new \RuntimeException(\sprintf('No entity for message %s', $message::class));
            }

            /**
             * @psalm-suppress MixedMethodCall
             * @var TEntity
             */
            $entity = $this->class::{$this->factoryMethod}($message, $messageContext);
        }

        /**
         * @psalm-suppress MixedMethodCall
         * @var TResult
         */
        $result = $entity->{$this->handlerMethod}($message, $messageContext);
        $this->saver->save($entity);

        return $result;
    }
}
