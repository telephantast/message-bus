<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\PreHandleLogging;

use Psr\Log\LoggerInterface;
use Telephantast\MessageBus\Handler\Pipeline;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;

/**
 * @api
 */
final readonly class PreHandleLoggingMiddleware implements Middleware
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
            'envelope' => $messageContext->envelope,
        ]);

        try {
            $result = $pipeline->continue();
        } catch (\Throwable $exception) {
            $this->logger->critical('Failed to handle message {message_class}.', [
                'exception' => $exception,
                'message_class' => $messageContext->getMessageClass(),
                'handler_id' => $pipeline->id(),
                'envelope' => $messageContext->envelope,
            ]);

            throw $exception;
        }

        $this->logger->debug('Successfully handled message {message_class}.', [
            'message_class' => $messageContext->getMessageClass(),
            'handler_id' => $pipeline->id(),
            'envelope' => $messageContext->envelope,
            'result' => $result,
        ]);

        return $result;
    }
}
