<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\Message\Message;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Handler;
use function Typhoon\Describe\describeReflectedDeclaration;

/**
 * @api
 * @template TResult
 * @template TMessage of Message<TResult>
 * @implements Handler<TResult, TMessage>
 */
final readonly class CallableHandler implements Handler
{
    /**
     * @var non-empty-string
     */
    private string $id;

    /**
     * @param callable(TMessage, Context<TResult, TMessage>): TResult $handler
     * @param ?non-empty-string $id
     */
    public function __construct(
        private mixed $handler,
        ?string $id = null,
    ) {
        $this->id = $id ?? describeReflectedDeclaration(new \ReflectionFunction($handler(...)));
    }

    public function id(): string
    {
        return $this->id;
    }

    public function handle(Context $context): mixed
    {
        return ($this->handler)($context->envelope->message, $context);
    }
}
