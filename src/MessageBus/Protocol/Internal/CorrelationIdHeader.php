<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Protocol\Internal;

use Thesis\Headers\CannotDecodeHeader;
use Thesis\Headers\Header;
use Thesis\MessageBus\Protocol\RoutedCorrelationId;

/**
 * @internal
 *
 * @implements Header<non-empty-string|RoutedCorrelationId>
 */
final class CorrelationIdHeader implements Header
{
    public const string NAME = 'thesis-correlation-id';

    public string $name { get => self::NAME; }

    public function encode(mixed $value): string
    {
        if (\is_string($value)) {
            return $value;
        }

        return json_encode(
            value: [
                'hq' => $value->handlerQualifier,
                'id' => $value->id,
            ],
            flags: JSON_THROW_ON_ERROR,
        );
    }

    public function decode(string $encoded): mixed
    {
        if ($encoded === '') {
            throw new CannotDecodeHeader(\sprintf(
                'Header "%s" has an empty value, expected a non-empty string.',
                $this->name,
            ));
        }

        try {
            $value = json_decode(
                json: $encoded,
                associative: true,
                flags: JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException) {
            return $encoded;
        }

        if (\is_array($value)
            && \count($value) === 2
            && \array_key_exists('hq', $value) && \is_string($value['hq']) && $value['hq'] !== ''
            && \array_key_exists('id', $value) && ($value['id'] === null || \is_string($value['id'])) && $value['id'] !== ''
        ) {
            return new RoutedCorrelationId(
                handlerQualifier: $value['hq'],
                id: $value['id'],
            );
        }

        return $encoded;
    }
}
