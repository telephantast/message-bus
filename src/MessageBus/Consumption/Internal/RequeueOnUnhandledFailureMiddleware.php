<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption\Internal;

use Psr\Log\LoggerInterface;
use Thesis\MessageBus\Consumption\ConsumerMiddleware;
use Thesis\MessageBus\Transport\ConsumerHandler;
use Thesis\MessageBus\Transport\Disposition;
use Thesis\MessageBus\Transport\InboundEnvelope;
use const Thesis\MessageBus\MESSAGE_ID;
use const Thesis\MessageBus\MESSAGE_TYPE;
use const Thesis\MessageBus\RETRY_COUNT;

/**
 * @internal
 */
final readonly class RequeueOnUnhandledFailureMiddleware implements ConsumerMiddleware
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function process(string $endpoint, InboundEnvelope $envelope, ConsumerHandler $handler): Disposition
    {
        try {
            return $handler->handle($envelope);
        } catch (\Throwable $error) {
            $this->logger->error('Handling failed; requeueing message.', [
                'exception' => $error,
                'endpoint' => $endpoint,
                'message_id' => $envelope->headers->find(MESSAGE_ID),
                'message_type' => $envelope->headers->find(MESSAGE_TYPE),
                'retry_count' => $envelope->headers->find(RETRY_COUNT),
            ]);

            return Disposition::Requeue;
        }
    }
}
