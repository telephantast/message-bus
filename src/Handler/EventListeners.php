<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Internal\FeaturedHandlerFactory;
use Thesis\MessageBus\Stamps;

/**
 * @template TTransaction of object = never
 */
final class EventListeners
{
    /**
     * @var list<class-string>
     */
    public array $eventClasses { get => array_keys($this->listeners); }

    /**
     * @var array<class-string, non-empty-list<callable(Envelope, Context<object, TTransaction>): void>>
     */
    private array $listeners = [];

    /**
     * @template TEvent of object
     * @template TListenerTransaction of object = never
     * @param non-empty-list<class-string<TEvent>> $messageClasses
     * @param callable(Envelope<TEvent>, Context<object, TListenerTransaction>): void $listener
     * @return self<TTransaction|TListenerTransaction>
     */
    public function with(array $messageClasses, mixed $listener): self
    {
        $copy = clone $this;

        foreach ($messageClasses as $messageClass) {
            /** @phpstan-ignore assign.propertyType */
            $copy->listeners[$messageClass][] = $listener;
        }

        return $copy;
    }

    /**
     * @template TEvent of object
     * @template TListenerTransaction of object = never
     * @param callable(TEvent, Context<object, TListenerTransaction>, Stamps): (void|null|Result<null>) $listener
     * @param list<Middleware> $middleware
     * @return self<TTransaction|TListenerTransaction>
     */
    public function withFeatured(callable $listener, array $middleware = []): self
    {
        /** @phpstan-ignore argument.type, return.type */
        return $this->with(...FeaturedHandlerFactory::create($listener, $middleware));
    }

    /**
     * @param Context<object, TTransaction> $context
     */
    public function __invoke(Envelope $event, Context $context): void
    {
        foreach ($this->listeners[$event->messageClass] ?? [] as $listener) {
            $listener($event, $context);
        }
    }
}
