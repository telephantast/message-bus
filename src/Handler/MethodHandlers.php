<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Internal\FeaturedHandlerFactory;
use Thesis\MessageBus\Method;
use Thesis\MessageBus\NestedInvoke;
use Thesis\MessageBus\Stamps;

/**
 * @template TTransaction of object = never
 * @template-contravariant TSupportedMethods of object = never
 * @implements NestedInvoke<TSupportedMethods>
 */
final class MethodHandlers implements NestedInvoke
{
    /**
     * @var list<class-string>
     */
    public array $methodClasses { get => array_keys($this->handlers); }

    /**
     * @var array<class-string, callable(Envelope, Context<object, TTransaction>): mixed>
     */
    private array $handlers = [];

    /**
     * @template TMethod of object
     * @template THandlerTransaction of object = never
     * @param non-empty-list<class-string<TMethod>> $messageClasses
     * @param callable(Envelope<TMethod>, Context<object, THandlerTransaction>): mixed $handler
     * @return self<TTransaction|THandlerTransaction, TSupportedMethods|TMethod>
     */
    public function with(array $messageClasses, mixed $handler): self
    {
        $copy = clone $this;

        foreach ($messageClasses as $messageClass) {
            if (isset($copy->handlers[$messageClass])) {
                throw new \LogicException();
            }

            /** @phpstan-ignore assign.propertyType */
            $copy->handlers[$messageClass] = $handler;
        }

        return $copy;
    }

    /**
     * @template TMethod of object
     * @template THandlerTransaction of object = never
     * @param callable(TMethod, Context<object, THandlerTransaction>, Stamps): mixed $handler
     * @param list<Middleware> $middleware
     * @return self<TTransaction|THandlerTransaction, TSupportedMethods|TMethod>
     */
    public function withFeatured(callable $handler, array $middleware = []): self
    {
        /** @phpstan-ignore return.type */
        return $this->with(...FeaturedHandlerFactory::create($handler, $middleware));
    }

    /**
     * @template TResult
     * @param Envelope<TSupportedMethods> $method
     * @param Context<object, TTransaction> $context
     * @return ($method is Envelope<Method<TResult>> ? TResult : mixed)
     */
    public function handle(Envelope $method, Context $context): mixed
    {
        return ($this->handlers[$method->messageClass])($method, $context);
    }

    public function nestedInvoke(Envelope $method, Context $parentContext): mixed
    {
        return $this->handle($method, $parentContext->child($parentContext->endpoint, $method));
    }
}
