<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Amp\Cancellation;
use Thesis\MessageBus\IdGenerator\Random;

/**
 * @api
 *
 * @template-contravariant T of object
 * @template-contravariant Tx of object
 */
final readonly class Handler
{
    /**
     * @var \Closure(Envelope<T>, Tx, Cancellation): Dispatch
     */
    private \Closure $function;

    /**
     * @param non-empty-string $name
     * @param callable(Envelope<T>, Tx, Cancellation): Dispatch $function
     */
    public function __construct(
        public string $name,
        callable $function,
        private IdGenerator $idGenerator = new Random(),
    ) {
        $this->function = $function(...);
    }

    /**
     * @param Envelope<T> $envelope
     * @param Tx $transaction
     * @return list<Envelope>
     */
    public function handle(Envelope $envelope, object $transaction, Cancellation $cancellation): array
    {
        $dispatch = ($this->function)($envelope, $transaction, $cancellation);

        return $dispatch->seal($this->name, $this->idGenerator, $envelope);
    }
}
