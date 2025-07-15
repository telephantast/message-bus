<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 * @template-contravariant TCalls of Call = never
 */
interface Invoker
{
    /**
     * @template TResult
     * @param (Call<TResult>&TCalls)|Envelope<Call<TResult>&TCalls> $call
     * @return TResult
     */
    public function invoke(Call|Envelope $call, Context $context = new Context()): mixed;
}
