<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\EntityHandler;

use Telephantast\Message\Message;

/**
 * @api
 * @template-covariant TResult
 * @template-covariant TMessage of Message<TResult>
 * @template-covariant TEntity of object
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final readonly class FactoryMethod
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        public string $name,
    ) {}
}
