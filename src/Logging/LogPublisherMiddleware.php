<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Logging;

use Psr\Log\LoggerInterface;
use Thesis\MessageBus\MessageContext;
use Thesis\MessageBus\Middleware;
use Thesis\MessageBus\Pipeline;

/**
 * @api
 */
final readonly class LogPublisherMiddleware implements Middleware
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    /**
     * @throws \Throwable
     */
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        $this->logger->info('About to publish message {message_class}.', [
            'message_class' => $messageContext->getMessageClass(),
            'envelope' => $messageContext->getEnvelope(),
        ]);

        try {
            $result = $pipeline->continue();
        } catch (\Throwable $exception) {
            $this->logger->critical('Failed to publish message {message_class}.', [
                'exception' => $exception,
                'message_class' => $messageContext->getMessageClass(),
                'envelope' => $messageContext->getEnvelope(),
            ]);

            throw $exception;
        }

        $this->logger->debug('Successfully published message {message_class}.', [
            'message_class' => $messageContext->getMessageClass(),
            'envelope' => $messageContext->getEnvelope(),
        ]);

        return $result;
    }
}
