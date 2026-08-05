<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Thesis\MessageBus\Consumption\ConsumerMiddleware;
use Thesis\MessageBus\Processing\Retry;
use Thesis\MessageBus\Recoverability\Action;
use Thesis\MessageBus\Recoverability\DeadLetterStorage;
use Thesis\MessageBus\Recoverability\FailureContext;
use Thesis\MessageBus\Recoverability\RecoverabilityPolicy;
use Thesis\MessageBus\Transport\ConsumerHandler;
use Thesis\MessageBus\Transport\Dispatcher;
use Thesis\MessageBus\Transport\Disposition;
use Thesis\MessageBus\Transport\InboundEnvelope;
use Thesis\MessageBus\Transport\Operation;
use Thesis\MessageBus\Transport\OutboundEnvelope;
use Thesis\Time\WallClock;
use const Thesis\MessageBus\MESSAGE_ID;
use const Thesis\MessageBus\MESSAGE_TYPE;
use const Thesis\MessageBus\RETRY_COUNT;
use const Thesis\MessageBus\RETRY_STARTED_AT;

/**
 * @internal
 */
final readonly class FailureHandlingMiddleware implements ConsumerMiddleware
{
    private const int MAX_IMMEDIATE_RETRIES = 10;

    public function __construct(
        private RecoverabilityPolicy $policy,
        private Dispatcher $dispatcher,
        private DeadLetterStorage $deadLetterStorage,
        private ClockInterface $clock = new WallClock(),
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function process(string $endpoint, InboundEnvelope $envelope, ConsumerHandler $handler): Disposition
    {
        $startedAt = null;
        $delayedRetryCount = null;
        $logContext = null;

        for ($immediateRetryCount = 0; $immediateRetryCount < self::MAX_IMMEDIATE_RETRIES; ++$immediateRetryCount) {
            try {
                return $handler->handle($envelope);
            } catch (\Throwable $error) {
                $startedAt ??= $envelope->headers->find(RETRY_STARTED_AT) ?? $this->clock->now();
                $delayedRetryCount ??= max(0, $envelope->headers->find(RETRY_COUNT) ?? 0);
                $logContext ??= [
                    'endpoint' => $endpoint,
                    'message_id' => $envelope->headers->find(MESSAGE_ID),
                    'message_type' => $envelope->headers->find(MESSAGE_TYPE),
                    'retry_count' => $envelope->headers->find(RETRY_COUNT),
                ];

                $context = new FailureContext(
                    endpoint: $endpoint,
                    error: $error,
                    startedAt: $startedAt,
                    immediateRetryCount: $immediateRetryCount,
                    delayedRetryCount: $delayedRetryCount,
                );

                $decision = $this->policy->onFailure($context) ?? Action::Bury;

                if ($decision instanceof Retry) {
                    if ($decision->delay->isNegativeOrZero()) {
                        $this->logger->warning('Message handling failed; retrying immediately.', [
                            ...$logContext,
                            'exception' => $error,
                            'immediate_retry_count' => $immediateRetryCount,
                            'delayed_retry_count' => $delayedRetryCount,
                        ]);

                        continue;
                    }

                    $this->logger->warning('Message handling failed; scheduling delayed retry.', [
                        ...$logContext,
                        'exception' => $error,
                        'immediate_retry_count' => $immediateRetryCount,
                        'delayed_retry_count' => $delayedRetryCount,
                        'delay_seconds' => $decision->delay->toSeconds(),
                    ]);

                    $this->dispatcher->dispatch([
                        new OutboundEnvelope(
                            operation: Operation::Send,
                            address: $endpoint,
                            payload: $envelope->payload,
                            headers: $envelope->headers
                                ->withDefault(RETRY_STARTED_AT, $startedAt)
                                ->with(RETRY_COUNT, $delayedRetryCount + 1),
                            delay: $decision->delay,
                        ),
                    ]);

                    return Disposition::Ack;
                }

                if ($decision === Action::Bury) {
                    $this->logger->error('Message handling failed; storing in dead letter.', $logContext + [
                        'exception' => $error,
                        'immediate_retry_count' => $immediateRetryCount,
                        'delayed_retry_count' => $delayedRetryCount,
                    ]);

                    $this->deadLetterStorage->store(
                        envelope: new InboundEnvelope(
                            payload: $envelope->payload,
                            headers: $envelope->headers
                                ->withDefault(RETRY_COUNT, $delayedRetryCount)
                                ->withDefault(RETRY_STARTED_AT, $startedAt),
                        ),
                        context: $context,
                    );

                    return Disposition::Ack;
                }

                $this->logger->warning('Message handling failed; discarding.', $logContext + [
                    'exception' => $error,
                    'immediate_retry_count' => $immediateRetryCount,
                    'delayed_retry_count' => $delayedRetryCount,
                ]);

                return Disposition::Ack;
            }
        }

        return Disposition::Requeue;
    }
}
