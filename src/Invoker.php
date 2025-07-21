<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 * @template-contravariant TSupportedCalls of object = never
 */
interface Invoker
{
    /**
     * @template TResult
     * @param TSupportedCalls|Envelope<TSupportedCalls> $call
     * @return ($call is (Call<TResult>|Envelope<Call<TResult>>) ? TResult : mixed)
     */
    public function invoke(object $call): mixed;
}
