<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;
use Thesis\MessageBus\Async\TransportOptions;

/**
 * @api
 * @template-covariant TResult = mixed
 * @template-covariant TMessage of Message<TResult> = Message<mixed>
 */
final readonly class Envelope
{
    /**
     * @param TMessage $message
     * @param non-empty-string $messageId
     * @param ?non-empty-string $causationId
     * @param ?non-empty-string $correlationId
     * @param array<string, mixed> $headers
     */
    public function __construct(
        public Message $message,
        public string $messageId,
        public ?string $causationId = null,
        public ?string $correlationId = null,
        public ?\DateTimeImmutable $timestamp = null,
        public array $headers = [],
        public ?TransportOptions $transportOptions = null,
    ) {}
}
