<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Pgmq;

use Amp\Postgres\PostgresConnection;
use Amp\Postgres\PostgresTransaction;
use Psr\Log\LoggerInterface;
use Thesis\MessageBus\Delivery;
use Thesis\MessageBus\Dispatcher;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Exception\Unrecoverable;
use Thesis\Pgmq;
use Thesis\Time\TimeSpan;

/**
 * @api
 *
 * @implements Dispatcher\Transactional<PostgresTransaction>
 * @implements Delivery<PostgresTransaction>
 */
final readonly class Transport implements Dispatcher, Dispatcher\Transactional, Delivery
{
    /**
     * @param ?Dispatcher\Transactional<PostgresTransaction> $receiverDispatcher use this if By default, messages are dispatched to this transport
     */
    public function __construct(
        private PostgresConnection $pg,
        private Router $router,
        private LoggerInterface $logger,
        private PayloadNormalizer $payloadNormalizer = new PayloadNormalizer\Serialize(),
        private MetadataNormalizer $metadataNormalizer = new MetadataNormalizer\Basic(),
        private ?Dispatcher\Transactional $receiverDispatcher = null,
    ) {}

    public function setup(): void
    {
        Pgmq\createExtension($this->pg);

        foreach ($this->router->queues as $queue) {
            Pgmq\createQueue($this->pg, $queue);
        }
    }

    public function dispatch(array $messages): void
    {
        $tx = $this->pg->beginTransaction();

        try {
            $this->transactionalDispatch($tx, $messages);

            $tx->commit();
        } catch (\Throwable $exception) {
            $tx->rollback();

            throw $exception;
        }
    }

    public function transactionalDispatch(object $transaction, array $messages): void
    {
        foreach ($messages as $message) {
            $sendMessage = null;

            foreach ($this->router->route($message->payload::class) as $queue) {
                Pgmq\send(
                    pg: $transaction,
                    queue: $queue,
                    message: $sendMessage ??= new Pgmq\SendMessage(
                        valueJson: self::encode($this->payloadNormalizer->normalizePayload($message->payload)),
                        headerJson: self::encode($this->metadataNormalizer->normalizeMetadata($message->metadata)),
                    ),
                    delay: $message->delay,
                );
            }
        }
    }

    /**
     * @return non-empty-string
     */
    private static function encode(mixed $data): string
    {
        return json_encode(value: $data, flags: JSON_THROW_ON_ERROR);
    }

    public function consume(string $consumer, callable $handler, Envelope $envelope): void
    {
        $tx = $this->pg->beginTransaction();

        try {
            $outgoing = $handler($envelope, $tx);

            if ($outgoing !== []) {
                ($this->receiverDispatcher ?? $this)->transactionalDispatch($tx, $outgoing);
            }

            $tx->commit();
        } catch (\Throwable $exception) {
            $tx->rollback();

            throw $exception;
        }
    }

    public function startConsumer(string $consumer, callable $handler): callable
    {
        $context = Pgmq\createConsumer($this->pg)->consume(
            handler: function (array $messages, Pgmq\ConsumeController $controller) use ($handler): void {
                try {
                    [$message] = $messages;

                    $metadata = $this->metadataNormalizer->denormalizeMetadata(self::decode($message->headers ?? '{}'));
                    $payload = $this->payloadNormalizer->denormalizePayload($metadata->class, self::decode($message->value));

                    $envelope = new Envelope($metadata, $payload);

                    $outgoing = $handler($envelope, $controller->tx);

                    if ($outgoing !== []) {
                        ($this->receiverDispatcher ?? $this)->transactionalDispatch($controller->tx, $outgoing);
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
                queue: $consumer,
                batch: 1,
            ),
        );

        return static function () use ($context): void {
            $context->stop();
            $context->awaitCompletion();
        };
    }

    private static function decode(string $json): mixed
    {
        return json_decode(json: $json, associative: true, flags: JSON_THROW_ON_ERROR);
    }
}
