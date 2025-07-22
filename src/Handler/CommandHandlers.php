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
final class CommandHandlers
{
    /**
     * @var list<class-string>
     */
    public array $commandClasses { get => array_keys($this->handlers); }

    /**
     * @var array<class-string, callable(Envelope, Context<TTransaction>): void>
     */
    private array $handlers = [];

    /**
     * @template TCommand of object
     * @template THandlerTransaction of object = never
     * @param non-empty-list<class-string<TCommand>> $messageClasses
     * @param callable(Envelope<TCommand>, Context<THandlerTransaction>): void $handler
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
     * @template TCommand of object
     * @template THandlerTransaction of object = never
     * @param callable(TCommand, Context<THandlerTransaction>, Stamps): (void|null|Result<null>) $handler
     * @param list<Middleware> $middleware
     * @return self<TTransaction|THandlerTransaction>
     */
    public function withFeatured(callable $handler, array $middleware = []): self
    {
        /** @phpstan-ignore argument.type, return.type */
        return $this->with(...FeaturedHandlerFactory::create($handler, $middleware));
    }

    /**
     * @param Context<TTransaction> $context
     */
    public function __invoke(Envelope $command, Context $context): void
    {
        ($this->handlers[$command->messageClass])($command, $context);
    }
}
