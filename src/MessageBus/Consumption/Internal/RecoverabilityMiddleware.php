<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption\Internal;

use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Thesis\Headers;
use Thesis\MessageBus\Consumption\ConsumerMiddleware;
use Thesis\MessageBus\Consumption\Recoverability\Action;
use Thesis\MessageBus\Consumption\Recoverability\FailureContext;
use Thesis\MessageBus\Consumption\Recoverability\RecoverabilityPolicy;
use Thesis\MessageBus\Consumption\Recoverability\Retry;
use Thesis\MessageBus\Transport\ConsumerHandler;
use Thesis\MessageBus\Transport\Dispatcher;
use Thesis\MessageBus\Transport\Disposition;
use Thesis\MessageBus\Transport\InboundEnvelope;
use Thesis\MessageBus\Transport\Operation;
use Thesis\MessageBus\Transport\OutboundEnvelope;
use Thesis\Time\TimeSpan;
use const Thesis\MessageBus\ERROR_CLASS;
use const Thesis\MessageBus\ERROR_FILE;
use const Thesis\MessageBus\ERROR_LINE;
use const Thesis\MessageBus\FAILURE_ENDPOINT;
use const Thesis\MessageBus\FIRST_FAILED_AT;
use const Thesis\MessageBus\MESSAGE_ID;
use const Thesis\MessageBus\MESSAGE_TYPE;
use const Thesis\MessageBus\RETRY_COUNT;

/**
 * @internal
 */
final readonly class RecoverabilityMiddleware implements ConsumerMiddleware
{
    private const int MAX_IMMEDIATE_RETRIES = 10;

    /**
     * @param non-empty-string $deadLetterQueue
     */
    public function __construct(
        private RecoverabilityPolicy $policy,
        private Dispatcher $dispatcher,
        private string $deadLetterQueue,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {}

    public function process(string $endpoint, InboundEnvelope $envelope, ConsumerHandler $handler): Disposition
    {
        $context = null;

        for ($immediateRetryCount = 0; $immediateRetryCount < self::MAX_IMMEDIATE_RETRIES; ++$immediateRetryCount) {
            try {
                return $handler->handle($envelope);
            } catch (\Throwable $error) {
                $startedAt ??= $envelope->headers->find(FIRST_FAILED_AT) ?? $this->clock->now();
                $delayedRetryCount ??= max(0, $envelope->headers->find(RETRY_COUNT) ?? 0);

                $context = new FailureContext(
                    endpoint: $endpoint,
                    error: $error,
                    firstFailedAt: $startedAt,
                    immediateRetryCount: $immediateRetryCount,
                    delayedRetryCount: $delayedRetryCount,
                );

                $action = $this->policy->onFailure($context);

                if ($action instanceof Retry) {
                    if ($action->delay->isNegativeOrZero()) {
                        $this->log(
                            level: LogLevel::WARNING,
                            message: 'Message handling failed; retrying immediately.',
                            envelope: $envelope,
                            failureContext: $context,
                        );

                        continue;
                    }

                    return $this->retry($envelope, $context, $action->delay);
                }

                return match ($action) {
                    Action::Bury, null => $this->bury($envelope, $context),
                    Action::Discard => $this->discard($envelope, $context),
                };
            }
        }

        \assert($context !== null);

        $this->log(
            level: LogLevel::ERROR,
            message: 'Invalid retry policy with 10 immediate retries.',
            envelope: $envelope,
            failureContext: $context,
        );

        return $this->bury($envelope, $context);
    }

    private function retry(InboundEnvelope $envelope, FailureContext $context, TimeSpan $delay): Disposition
    {
        $this->log(
            level: LogLevel::WARNING,
            message: 'Message handling failed; scheduling delayed retry.',
            envelope: $envelope,
            failureContext: $context,
            context: ['delay' => $delay->format()],
        );

        $this->dispatcher->dispatch([
            new OutboundEnvelope(
                operation: Operation::Send,
                address: $context->endpoint,
                payload: $envelope->payload,
                headers: self::enrichHeaders(
                    headers: $envelope->headers,
                    context: $context,
                    retryCount: $context->delayedRetryCount + 1,
                ),
                delay: $delay,
            ),
        ]);

        return Disposition::Nack;
    }

    private function bury(InboundEnvelope $envelope, FailureContext $context): Disposition
    {
        $this->log(
            level: LogLevel::ERROR,
            message: 'Message handling failed; storing in dead letter.',
            envelope: $envelope,
            failureContext: $context,
        );

        $this->dispatcher->dispatch([
            new OutboundEnvelope(
                operation: Operation::Send,
                address: $this->deadLetterQueue,
                payload: $envelope->payload,
                headers: self::enrichHeaders(
                    headers: $envelope->headers,
                    context: $context,
                    retryCount: $context->delayedRetryCount,
                ),
            ),
        ]);

        return Disposition::Nack;
    }

    private function discard(InboundEnvelope $envelope, FailureContext $context): Disposition
    {
        $this->log(
            level: LogLevel::WARNING,
            message: 'Message handling failed; discarding.',
            envelope: $envelope,
            failureContext: $context,
        );

        return Disposition::Nack;
    }

    /**
     * @param non-negative-int $retryCount
     */
    private static function enrichHeaders(Headers $headers, FailureContext $context, int $retryCount): Headers
    {
        return $headers
            ->withDefault(FIRST_FAILED_AT, $context->firstFailedAt)
            ->with(FAILURE_ENDPOINT, $context->endpoint)
            ->with(ERROR_CLASS, $context->error::class)
            ->with(ERROR_FILE, $context->error->getFile())
            ->with(ERROR_LINE, $context->error->getLine())
            ->with(RETRY_COUNT, $retryCount);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function log(string $level, string $message, InboundEnvelope $envelope, FailureContext $failureContext, array $context = []): void
    {
        $this->logger->log($level, $message, [
            'exception' => $failureContext->error,
            'endpoint' => $failureContext->endpoint,
            'message_id' => $envelope->headers->find(MESSAGE_ID),
            'message_type' => $envelope->headers->find(MESSAGE_TYPE),
            'immediate_retry_count' => $failureContext->immediateRetryCount,
            'delayed_retry_count' => $failureContext->delayedRetryCount,
            ...$context,
        ]);
    }
}
