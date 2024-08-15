<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Logging;

use Psr\Log\LoggerInterface;
use Telephantast\MessageBus\Handler\Pipeline;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;

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
            'envelope' => $messageContext->envelope,
        ]);

        try {
            $result = $pipeline->continue();
        } catch (\Throwable $exception) {
            $this->logger->critical('Failed to publish message {message_class}.', [
                'exception' => $exception,
                'message_class' => $messageContext->getMessageClass(),
                'envelope' => $messageContext->envelope,
            ]);

            throw $exception;
        }

        $this->logger->debug('Successfully published message {message_class}.', [
            'message_class' => $messageContext->getMessageClass(),
            'envelope' => $messageContext->envelope,
        ]);

        return $result;
    }
}
