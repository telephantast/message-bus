<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\MessageBus\Call;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Internal\FeaturedHandlerFactory;
use Thesis\MessageBus\Stamps;

/**
 * @template TTransaction of object = never
 */
final class CallHandlers
{
    /**
     * @var list<class-string>
     */
    public array $callClasses { get => array_keys($this->handlers); }

    /**
     * @var array<class-string, callable(Envelope, Context<TTransaction>): mixed>
     */
    private array $handlers = [];

    /**
     * @template TCall of object
     * @template THandlerTransaction of object = never
     * @param non-empty-list<class-string<TCall>> $messageClasses
     * @param callable(Envelope<TCall>, Context<THandlerTransaction>): mixed $handler
     * @return self<TTransaction|THandlerTransaction>
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
     * @template TCall of object
     * @template THandlerTransaction of object = never
     * @param callable(TCall, Context<THandlerTransaction>, Stamps): mixed $handler
     * @param list<Middleware> $middleware
     * @return self<TTransaction|THandlerTransaction>
     */
    public function withFeatured(callable $handler, array $middleware = []): self
    {
        /** @phpstan-ignore return.type */
        return $this->with(...FeaturedHandlerFactory::create($handler, $middleware));
    }

    /**
     * @template TResult
     * @param Context<TTransaction> $context
     * @return ($call is Envelope<Call<TResult>> ? TResult : mixed)
     */
    public function handle(Envelope $call, Context $context): mixed
    {
        return ($this->handlers[$call->messageClass])($call, $context);
    }
}
