<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\Message\Message;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\Handler\CallableHandler\Parameters;
use Thesis\MessageBus\Publisher;
use Thesis\MessageBus\Sender;

/**
 * @template TMessage of Message
 * @implements Handler<TMessage>
 */
final class CallableHandler implements Handler
{
    /**
     * @param callable $handler
     * @param ?list<class-string<TMessage>> $messageClasses
     */
    public function __construct(
        private readonly mixed $handler,
        ?array $messageClasses = null,
    ) {
        $this->_messageClasses = $messageClasses;
    }

    /**
     * @var ?list<class-string<TMessage>>
     */
    private readonly ?array $_messageClasses;

    public array $messageClasses { get => $this->_messageClasses ?? $this->parameters->messageClasses; }

    private ?Parameters $_parameters = null;

    private Parameters $parameters {
        get => $this->_parameters ??= Parameters::from(new \ReflectionFunction(($this->handler)(...))->getParameters());
    }

    public function handle(string $endpoint, Envelope $envelope, Context $context): mixed
    {
        $arguments = $this->parameters->resolveArguments($endpoint, $envelope, $context);
        $result = ($this->handler)(...$arguments);

        if ($result instanceof Result) {
            $context->get(Sender::class)->send(...$result->commands);
            $context->get(Publisher::class)->publish(...$result->events);

            return $result->result;
        }

        return $result;
    }
}
