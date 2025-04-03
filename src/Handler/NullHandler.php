<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\Message\Message;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Handler;

/**
 * @api
 * @template TMessage of Message<null>
 * @implements Handler<null, TMessage>
 */
final readonly class NullHandler implements Handler
{
    /**
     * @param non-empty-string $id
     */
    public function __construct(
        private string $id = self::class,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function handle(Context $context): mixed
    {
        return null;
    }
}
