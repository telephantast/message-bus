<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\Headers;
use Thesis\MessageBus\Identification\IdGenerator;
use Thesis\MessageBus\InvalidOutboundMessage;
use Thesis\MessageBus\Metadata\MessageKind;
use Thesis\MessageBus\Publish;
use Thesis\MessageBus\Reply;
use Thesis\MessageBus\ReplyTo;
use Thesis\MessageBus\Send;
use Thesis\MessageBus\Serialization\Serializer;
use Thesis\MessageBus\Transport\Operation;
use Thesis\MessageBus\Transport\OutboundEnvelope;
use const Thesis\MessageBus\CAUSE_ID;
use const Thesis\MessageBus\CONTENT_ENCODING;
use const Thesis\MessageBus\CONTENT_TYPE;
use const Thesis\MessageBus\CONVERSATION_ID;
use const Thesis\MessageBus\CORRELATION_ID;
use const Thesis\MessageBus\MESSAGE_ID;
use const Thesis\MessageBus\MESSAGE_TYPE;
use const Thesis\MessageBus\ORIGIN_ENDPOINT;

/**
 * @internal
 */
final readonly class OutboundEnvelopeFactory
{
    public function __construct(
        private MessageMetadataRegistry $messageMetadataRegistry,
        private Serializer $serializer,
        private IdGenerator $idGenerator,
    ) {}

    /**
     * @param non-empty-string $originEndpoint
     */
    public function build(Send|Publish|Reply $intent, string $originEndpoint, Headers $causeHeaders = new Headers()): OutboundEnvelope
    {
        return match ($intent::class) {
            Send::class => $this->buildCommand($intent, $originEndpoint, $causeHeaders),
            Publish::class => $this->buildEvent($intent, $originEndpoint, $causeHeaders),
            Reply::class => $this->buildReply($intent, $originEndpoint, $causeHeaders),
        };
    }

    /**
     * @param non-empty-string $originEndpoint
     */
    private function buildCommand(Send $send, string $originEndpoint, Headers $causeHeaders = new Headers()): OutboundEnvelope
    {
        $metadata = $this->messageMetadataRegistry->forClass($send->command::class);

        if (!$metadata->isCommand) {
            throw new InvalidOutboundMessage(\sprintf(
                'Cannot send %s "%s": expected command.',
                self::kindName($metadata->kind),
                $send->command::class,
            ));
        }

        $serializedMessage = $this->serializer->serialize($send->command);

        return new OutboundEnvelope(
            operation: Operation::Send,
            address: $send->destinationEndpoint
                ?? $metadata->destinationEndpoint
                ?? throw new InvalidOutboundMessage(\sprintf(
                    'Command "%s" has no destination endpoint. Pass destination explicitly or configure SendTo.',
                    $send->command::class,
                )),
            payload: $serializedMessage->payload,
            headers: $this->buildHeaders(
                headers: $send->headers,
                originEndpoint: $originEndpoint,
                type: $metadata->type,
                contentType: $serializedMessage->contentType,
                contentEncoding: $serializedMessage->contentEncoding,
                causeHeaders: $causeHeaders,
            ),
            delay: $send->delay,
            transportOptions: $send->transportOptions,
        );
    }

    /**
     * @param non-empty-string $originEndpoint
     */
    private function buildEvent(Publish $publish, string $originEndpoint, Headers $causeHeaders = new Headers()): OutboundEnvelope
    {
        $metadata = $this->messageMetadataRegistry->forClass($publish->event::class);

        if (!$metadata->isEvent) {
            throw new InvalidOutboundMessage(\sprintf(
                'Cannot publish %s "%s": expected event.',
                self::kindName($metadata->kind),
                $publish->event::class,
            ));
        }

        $serializedMessage = $this->serializer->serialize($publish->event);

        return new OutboundEnvelope(
            operation: Operation::Publish,
            address: $metadata->type,
            payload: $serializedMessage->payload,
            headers: $this->buildHeaders(
                headers: $publish->headers,
                originEndpoint: $originEndpoint,
                type: $metadata->type,
                contentType: $serializedMessage->contentType,
                contentEncoding: $serializedMessage->contentEncoding,
                causeHeaders: $causeHeaders,
            ),
            transportOptions: $publish->transportOptions,
        );
    }

    /**
     * @param non-empty-string $originEndpoint
     */
    private function buildReply(Reply $reply, string $originEndpoint, Headers $causeHeaders = new Headers()): OutboundEnvelope
    {
        $to = $reply->to ?? ReplyTo::fromRequestHeaders($causeHeaders);

        $metadata = $this->messageMetadataRegistry->forClass($reply->reply::class);

        if (!$metadata->isReply) {
            throw new InvalidOutboundMessage(\sprintf(
                'Cannot reply with %s "%s": expected reply.',
                self::kindName($metadata->kind),
                $reply->reply::class,
            ));
        }

        $serializedMessage = $this->serializer->serialize($reply->reply);

        return new OutboundEnvelope(
            operation: Operation::Send,
            address: $to->destinationEndpoint,
            payload: $serializedMessage->payload,
            headers: $this->buildHeaders(
                headers: $reply->headers
                    ->withDefault(CONVERSATION_ID, $to->conversationId)
                    ->withDefault(CORRELATION_ID, $to->correlationId),
                originEndpoint: $originEndpoint,
                type: $metadata->type,
                contentType: $serializedMessage->contentType,
                contentEncoding: $serializedMessage->contentEncoding,
                causeHeaders: $causeHeaders,
            ),
            transportOptions: $reply->transportOptions,
        );
    }

    /**
     * @param non-empty-string $originEndpoint
     * @param non-empty-string $type
     * @param ?non-empty-string $contentType
     * @param ?non-empty-string $contentEncoding
     */
    private function buildHeaders(
        Headers $headers,
        string $originEndpoint,
        string $type,
        ?string $contentType,
        ?string $contentEncoding,
        Headers $causeHeaders,
    ): Headers {
        $headers = $headers
            ->withDefault(MESSAGE_ID, $this->idGenerator->generateId(...))
            ->with(MESSAGE_TYPE, $type)
            ->with(ORIGIN_ENDPOINT, $originEndpoint);

        if ($contentType !== null) {
            $headers = $headers->with(CONTENT_TYPE, $contentType);
        }

        if ($contentEncoding !== null) {
            $headers = $headers->with(CONTENT_ENCODING, $contentEncoding);
        }

        $causeId = $causeHeaders->find(MESSAGE_ID);

        if ($causeId === null) {
            return $headers->withDefault(CONVERSATION_ID, static fn() => $headers->get(MESSAGE_ID));
        }

        return $headers
            ->with(CAUSE_ID, $causeId)
            ->withDefault(CONVERSATION_ID, static fn() => $causeHeaders->find(CONVERSATION_ID) ?? $causeId);
    }

    private static function kindName(MessageKind $kind): string
    {
        return match ($kind) {
            MessageKind::Command => 'command',
            MessageKind::Event => 'event',
            MessageKind::Reply => 'reply',
        };
    }
}
