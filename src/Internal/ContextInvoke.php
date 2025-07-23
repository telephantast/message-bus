<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Method;

/**
 * @template-contravariant TSupportedMethods of object
 */
interface ContextInvoke
{
    /**
     * @template TResult
     * @param Envelope<TSupportedMethods> $method
     * @return ($method is Envelope<Method<TResult>> ? TResult : mixed)
     */
    public function invoke(Envelope $method, Context $parentContext): mixed;
}
