<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Examples\HandlerCustomId;

use Thesis\Message\Call;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\EndpointConfig;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler\Mapping\Id;
use Thesis\MessageBus\Handler\Middleware;
use Thesis\MessageBus\Handler\Pipeline;
use Thesis\MessageBus\Handlers;
use Thesis\MessageBus\MessageBus;
use Thesis\MessageBus\MessageMatcher\Namespaced;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * @implements Call<\DateTimeImmutable>
 */
final readonly class GetTimestamp implements Call {}

final class DumpHandlerId implements Middleware
{
    public function handle(Envelope $envelope, Context $context, Pipeline $pipeline): mixed
    {
        dump($pipeline->handlerId);

        return $pipeline->continue();
    }
}

#[Id('This is my GetTimestamp handler!')]
function getTimestampHandler(GetTimestamp $_q): \DateTimeImmutable
{
    return new \DateTimeImmutable();
}

$messageBus = MessageBus::build([
    'test' => new EndpointConfig(
        handlers: new Handlers()->withBasic(getTimestampHandler(...)),
        handlesCall: new Namespaced(__NAMESPACE__),
    ),
]);

$messageBus->invoke(new GetTimestamp());
