<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handling\HandlingContext;
use Thesis\MessageBus\Middleware;
use Thesis\MessageBus\Pipeline;

/**
 * @api
 */
final readonly class LoggingMiddleware implements Middleware
{
    public function __construct(
        private LoggerInterface $logger,
        private mixed $aboutToHandleLevel = LogLevel::DEBUG,
        private mixed $failedToHandleLevel = LogLevel::ERROR,
        private mixed $successfullyHandledLevel = LogLevel::DEBUG,
    ) {}

    public function handle(Envelope $envelope, HandlingContext $context, Pipeline $pipeline): void
    {
        $this->logger->log($this->aboutToHandleLevel, 'About to handle message {message_class}', [
            'message_class' => $envelope->messageClass,
            'handler_id' => $pipeline->handlerId,
            'envelope' => $envelope,
        ]);

        try {
            $pipeline->continue();
        } catch (\Throwable $exception) {
            $this->logger->log($this->failedToHandleLevel, 'Failed to handle message {message_class}', [
                'exception' => $exception,
                'message_class' => $envelope->messageClass,
                'handler_id' => $pipeline->handlerId,
                'envelope' => $envelope,
            ]);

            throw $exception;
        }

        $this->logger->log($this->successfullyHandledLevel, 'Successfully handled message {message_class}', [
            'message_class' => $envelope->messageClass,
            'handler_id' => $pipeline->handlerId,
            'envelope' => $envelope,
        ]);
    }
}
