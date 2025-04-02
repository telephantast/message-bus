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
final readonly class LogHandlerMiddleware implements Middleware
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    /**
     * @throws \Throwable
     */
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        $this->logger->info('About to handle message {message_class}.', [
            'message_class' => $messageContext->getMessageClass(),
            'handler_id' => $pipeline->id(),
            'envelope' => $messageContext->getEnvelope(),
        ]);

        try {
            $result = $pipeline->continue();
        } catch (\Throwable $exception) {
            $this->logger->critical('Failed to handle message {message_class}.', [
                'exception' => $exception,
                'message_class' => $messageContext->getMessageClass(),
                'handler_id' => $pipeline->id(),
                'envelope' => $messageContext->getEnvelope(),
            ]);

            throw $exception;
        }

        $this->logger->debug('Successfully handled message {message_class}.', [
            'message_class' => $messageContext->getMessageClass(),
            'handler_id' => $pipeline->id(),
            'envelope' => $messageContext->getEnvelope(),
            'result' => $result,
        ]);

        return $result;
    }
}
