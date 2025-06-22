<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @template-contravariant TCalls of Call = never
 */
abstract class Invoker
{
    /**
     * @template TResult
     * @param (Call<TResult>&TCalls)|Envelope<Call<TResult>&TCalls> $call
     * @return TResult
     */
    final public function invoke(Call|Envelope $call): mixed
    {
        if ($call instanceof Envelope) {
            return $this->invokeEnvelope($call);
        }

        return $this->invokeEnvelope(new Envelope($call));
    }

    /**
     * @template TResult
     * @param Envelope<Call<TResult>&TCalls> $call
     * @return TResult
     */
    abstract protected function invokeEnvelope(Envelope $call): mixed;
}
