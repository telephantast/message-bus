<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling\Internal;

use Thesis\Headers;
use Thesis\MessageBus\Identification\IdGenerator;
use Thesis\MessageBus\InvalidIntent;
use Thesis\MessageBus\Metadata\Internal\MessageMetadataFactory;
use Thesis\MessageBus\Metadata\MessageKind;
use Thesis\MessageBus\Protocol\Serializer;
use Thesis\MessageBus\Publish;
use Thesis\MessageBus\Reply;
use Thesis\MessageBus\ReplyTo;
use Thesis\MessageBus\Routing\CannotRouteCommand;
use Thesis\MessageBus\Routing\CommandRouter;
use Thesis\MessageBus\Send;
use Thesis\MessageBus\Transport\Operation;
use Thesis\MessageBus\Transport\OutboundEnvelope;
use Thesis\Time\TimeSpan;
use const Thesis\MessageBus\Protocol\CAUSE_ID;
use const Thesis\MessageBus\Protocol\CONTENT_ENCODING;
use const Thesis\MessageBus\Protocol\CONTENT_TYPE;
use const Thesis\MessageBus\Protocol\CONVERSATION_ID;
use const Thesis\MessageBus\Protocol\CORRELATION_ID;
use const Thesis\MessageBus\Protocol\MESSAGE_ID;
use const Thesis\MessageBus\Protocol\MESSAGE_TYPE;
use const Thesis\MessageBus\Protocol\ORIGIN_ENDPOINT;

/**
 * @internal
 */
final class OutboundEnvelopeFactory
{
    public function __construct(
        private readonly MessageMetadataFactory $messageMetadataFactory,
        private readonly CommandRouter $commandRouter,
        private readonly Serializer $serializer,
        private readonly IdGenerator $idGenerator,
    ) {}

    /**
     * @param non-empty-string $originEndpoint
     * @throws InvalidIntent
     * @throws CannotRouteCommand
     */
    public function build(Send|Publish|Reply $intent, string $originEndpoint, Headers $causeHeaders = new Headers()): OutboundEnvelope
    {
        $class = $intent->message::class;
        $metadata = $this->messageMetadataFactory->forClass($class);
        $headers = $intent->headers;
        $delay = new TimeSpan(0);

        if ($intent instanceof Send) {
            if ($metadata->kind !== MessageKind::Command) {
                throw new InvalidIntent(\sprintf(
                    'Cannot send %s "%s": expected command.',
                    lcfirst($metadata->kind->name),
                    $class,
                ));
            }

            $operation = Operation::Send;
            $address = $intent->destination ?? $this->routeCommand($class);
            $delay = $intent->delay;
        } elseif ($intent instanceof Publish) {
            if ($metadata->kind !== MessageKind::Event) {
                throw new InvalidIntent(\sprintf(
                    'Cannot publish %s "%s": expected event.',
                    lcfirst($metadata->kind->name),
                    $class,
                ));
            }

            $operation = Operation::Publish;
            $address = $metadata->type;
        } else {
            if ($metadata->kind !== MessageKind::Reply) {
                throw new InvalidIntent(\sprintf(
                    'Cannot reply with %s "%s": expected reply.',
                    lcfirst($metadata->kind->name),
                    $class,
                ));
            }

            $to = $intent->to ?? ReplyTo::fromHeaders($causeHeaders);
            $operation = Operation::Send;
            $address = $to->destination;
            $headers = $headers->withDefault(CONVERSATION_ID, $to->conversationId);

            if ($to->correlationId !== null) {
                $headers = $headers->withDefault(CORRELATION_ID, $to->correlationId);
            }
        }

        $serializedMessage = $this->serializer->serialize($intent->message);

        return new OutboundEnvelope(
            operation: $operation,
            address: $address,
            payload: $serializedMessage->payload,
            headers: $this->buildHeaders(
                headers: $headers,
                originEndpoint: $originEndpoint,
                type: $metadata->type,
                contentType: $serializedMessage->contentType,
                contentEncoding: $serializedMessage->contentEncoding,
                causeHeaders: $causeHeaders,
            ),
            delay: $delay,
            transportOptions: $intent->transportOptions,
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

    /**
     * @var array<class-string, non-empty-string>
     */
    private array $commandEndpoints = [];

    /**
     * @param class-string $commandClass
     * @return non-empty-string
     * @throws CannotRouteCommand
     */
    public function routeCommand(string $commandClass): string
    {
        return $this->commandEndpoints[$commandClass]
            ??= $this->commandRouter->destinationFor($commandClass)
            ?? throw new CannotRouteCommand($commandClass);
    }
}
