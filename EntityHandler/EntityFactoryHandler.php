<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\EntityHandler;

use Telephantast\Message\Message;
use Telephantast\MessageBus\Handler;
use Telephantast\MessageBus\MessageContext;

/**
 * @api
 * @template TMessage of Message<void>
 * @template TEntity of object
 * @implements Handler<void, TMessage>
 * @psalm-suppress UnusedProperty
 */
final class EntityFactoryHandler implements Handler
{
    /**
     * @param non-empty-string $id
     * @param class-string<TEntity> $class
     * @param non-empty-string $factoryMethod
     */
    public function __construct(
        private readonly string $id,
        private readonly string $class,
        private readonly EntityFinder $finder,
        private readonly FindBy $findBy,
        private readonly string $factoryMethod,
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
            /**
             * @psalm-suppress MixedMethodCall
             * @var TEntity
             */
            $entity = $this->class::{$this->factoryMethod}($message, $messageContext);
            $this->saver->save($entity);
        }

        return null;
    }
}
