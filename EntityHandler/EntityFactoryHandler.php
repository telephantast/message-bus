<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\EntityHandler;

use Telephantast\Message\Message;
use Telephantast\MessageBus\Handler;
use Telephantast\MessageBus\MessageContext;

/**
 * @api
 * @template TMessage of Message<null>
 * @template TEntity of object
 * @implements Handler<null, TMessage>
 */
final readonly class EntityFactoryHandler implements Handler
{
    /**
     * @var non-empty-string
     * @psalm-suppress UnusedProperty
     */
    private string $factoryMethod;

    /**
     * @param non-empty-string $id
     * @param class-string<TEntity> $class
     * @param non-empty-string $factoryMethod
     */
    public function __construct(
        private string $id,
        private string $class,
        private EntityFinder $finder,
        private FindBy $findBy,
        string $factoryMethod,
        private EntitySaver $saver,
    ) {
        $this->factoryMethod = $factoryMethod;
    }

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
