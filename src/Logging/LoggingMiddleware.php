<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Thesis\MessageBus\Context;
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

    /**
     * @throws \Throwable
     */
    public function handle(Context $context, Pipeline $pipeline): mixed
    {
        $this->logger->log($this->aboutToHandleLevel, 'About to handle message {message_class}', [
            'message_class' => $context->envelope->message::class,
            'handler_id' => $pipeline->handlerId(),
            'envelope' => $context->envelope,
        ]);

        try {
            $result = $pipeline->continue();
        } catch (\Throwable $exception) {
            $this->logger->log($this->failedToHandleLevel, 'Failed to handle message {message_class}', [
                'exception' => $exception,
                'message_class' => $context->envelope->message::class,
                'handler_id' => $pipeline->handlerId(),
                'envelope' => $context->envelope,
            ]);

            throw $exception;
        }

        $this->logger->log($this->successfullyHandledLevel, 'Successfully handled message {message_class}', [
            'message_class' => $context->envelope->message::class,
            'handler_id' => $pipeline->handlerId(),
            'envelope' => $context->envelope,
            'result' => $result,
        ]);

        return $result;
    }
}
