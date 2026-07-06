<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Pgmq;

use Amp\Postgres\PostgresConnection;
use Amp\Postgres\PostgresLink;
use Amp\Postgres\PostgresTransaction;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Thesis\MessageBus\ConsumerRuntime;
use Thesis\MessageBus\ConsumerRuntime\Consumer;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Exception\Unrecoverable;
use Thesis\MessageBus\Route\Direct;
use Thesis\MessageBus\Subscriber;
use Thesis\MessageBus\TransactionalDispatcher;
use Thesis\Pgmq;
use Thesis\Time\TimeSpan;

/**
 * @api
 *
 * @implements TransactionalDispatcher<PostgresLink>
 * @implements ConsumerRuntime<PostgresTransaction>
 */
final readonly class Transport implements Subscriber, TransactionalDispatcher, ConsumerRuntime
{
    /**
     * @param ?TransactionalDispatcher<PostgresTransaction> $dispatcher By default, messages are dispatched to this transport
     */
    public function __construct(
        private PostgresConnection $pg,
        private Serializer $serializer = new NativeSerializer(),
        private LoggerInterface $logger = new NullLogger(),
        private ?TransactionalDispatcher $dispatcher = null,
    ) {}

    public function setup(): void
    {
        Pgmq\createExtension($this->pg);
    }

    public function subscribe(string $endpoint, array $eventClasses): void
    {
        Pgmq\createQueue($this->pg, $endpoint);

        $patterns = array_unique(array_map(self::eventRoutingKey(...), $eventClasses));

        $boundPatterns = [];

        /** @var array{pattern: string} $row */
        foreach ($this->pg->execute('select pattern from pgmq.list_topic_bindings(?)', [$endpoint]) as $row) {
            $boundPatterns[] = $row['pattern'];
        }

        foreach (array_diff($patterns, $boundPatterns) as $pattern) {
            $this->pg->execute('select pgmq.bind_topic(?, ?)', [$pattern, $endpoint]);
        }

        foreach (array_diff($boundPatterns, $patterns) as $pattern) {
            $this->pg->execute('select pgmq.unbind_topic(?, ?)', [$pattern, $endpoint]);
        }
    }

    public function dispatch(array $envelopes): void
    {
        $this->transactionalDispatch($this->pg, $envelopes);
    }

    public function transactionalDispatch(object $transaction, array $envelopes): void
    {
        $cols = [];
        $params = [];

        foreach ($envelopes as $outgoing) {
            $message = $this->serializer->serialize($outgoing->envelope);

            if ($outgoing->route instanceof Direct) {
                $cols[] = 'pgmq.send(?, ?, ?, ?::int)';
                $params[] = $outgoing->route->destination;
                $params[] = $message->valueJson;
                $params[] = $message->headerJson;
                $params[] = $outgoing->delay->toSeconds();
            } else {
                $cols[] = 'pgmq.send_topic(?, ?, ?, ?::int)';
                $params[] = self::eventRoutingKey($outgoing->route->eventClass);
                $params[] = $message->valueJson;
                $params[] = $message->headerJson;
                $params[] = $outgoing->delay->toSeconds();
            }
        }

        $transaction
            ->execute('select ' . implode(', ', $cols), $params)
            ->getRowCount();
    }

    public function consume(string $endpoint, Envelope $envelope, callable $handler): void
    {
        $tx = $this->pg->beginTransaction();

        try {
            $outgoing = $handler($envelope, $tx);

            if ($outgoing !== []) {
                ($this->dispatcher ?? $this)->transactionalDispatch($tx, $outgoing);
            }

            $tx->commit();
        } catch (\Throwable $exception) {
            $tx->rollback();

            throw $exception;
        }
    }

    public function startConsumer(string $endpoint, callable $handler): Consumer
    {
        Pgmq\createQueue($this->pg, $endpoint);

        $context = Pgmq\createConsumer($this->pg)->consume(
            handler: function (array $messages, Pgmq\ConsumeController $controller) use ($handler): void {
                try {
                    $envelope = $this->serializer->deserialize($messages[0]);

                    $outgoing = $handler($envelope, $controller->tx);

                    if ($outgoing !== []) {
                        ($this->dispatcher ?? $this)->transactionalDispatch($controller->tx, $outgoing);
                    }
                } catch (Unrecoverable $exception) {
                    $this->logger->error('Message rejected: ' . $exception->getMessage(), [
                        'exception' => $exception,
                    ]);

                    $controller->term($messages);

                    return;
                } catch (\Throwable $exception) {
                    $this->logger->error('Message scheduled for retry: ' . $exception->getMessage(), [
                        'exception' => $exception,
                    ]);

                    // todo strategy
                    $controller->nack($messages, TimeSpan::fromSeconds(2));

                    return;
                }

                $controller->ack($messages);
            },
            config: new Pgmq\ConsumeConfig(
                queue: $endpoint,
                batch: 1,
            ),
        );

        return new Internal\Consumer($context);
    }

    /**
     * @param class-string $class
     * @return non-empty-string
     */
    private static function eventRoutingKey(string $class): string
    {
        return str_replace('\\', '.', $class);
    }
}
