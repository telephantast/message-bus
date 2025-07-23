<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @template-contravariant TSupportedMethods of object = never
 */
interface NestedInvoke
{
    /**
     * @template TResult
     * @param Envelope<TSupportedMethods> $method
     * @return ($method is Envelope<Method<TResult>> ? TResult : mixed)
     */
    public function nestedInvoke(Envelope $method, Context $parentContext): mixed;
}
