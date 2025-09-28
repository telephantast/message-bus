<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Amp\Cancellation;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Thesis\MessageBus\Inbox\Lock;
use Thesis\MessageBus\Internal\InboxedHandler;
use Thesis\Transaction;

/**
 * @api
 *
 * @template-covariant Tx of object
 * @implements ReliableReceiver<Tx>
 */
final readonly class Inbox implements ReliableReceiver
{
    /**
     * @var \Closure(): Transaction<Tx>
     */
    private \Closure $beginTransaction;

    /**
     * @param callable(): Transaction<Tx> $beginTransaction
     * @param Lock<Tx> $inbox
     * @param ReliableDispatcher<Tx> $dispatcher
     */
    public function __construct(
        private Receiver $receiver,
        callable $beginTransaction,
        private Lock $inbox,
        private ReliableDispatcher $dispatcher,
        private LoggerInterface $logger = new NullLogger(),
    ) {
        $this->beginTransaction = $beginTransaction(...);
    }

    public function consume(string $name, callable $handler, ErrorHandler $errorHandler, Cancellation $cancellation): callable
    {
        return $this->receiver->consume(
            name: $name,
            handler: new InboxedHandler(
                consumer: $name,
                handler: $handler(...),
                errorHandler: $errorHandler,
                cancellation: $cancellation,
                beginTransaction: $this->beginTransaction,
                inbox: $this->inbox,
                dispatcher: $this->dispatcher,
                logger: $this->logger,
            ),
            cancellation: $cancellation,
        );
    }
}
