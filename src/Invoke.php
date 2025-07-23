<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 * @template-contravariant TSupportedMethods of object = never
 */
interface Invoke
{
    /**
     * @template TResult
     * @param TSupportedMethods|Envelope<TSupportedMethods> $method
     * @return ($method is (Method<TResult>|Envelope<Method<TResult>>) ? TResult : mixed)
     */
    public function __invoke(object $method): mixed;
}
