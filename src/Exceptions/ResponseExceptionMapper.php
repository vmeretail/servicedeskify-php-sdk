<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Exceptions;

use JsonException;
use Saloon\Http\Response;

final class ResponseExceptionMapper
{
    public static function throwIfFailed(Response $response): void
    {
        if (! $response->failed()) {
            return;
        }

        throw self::fromResponse($response);
    }

    public static function fromResponse(Response $response): ServiceDeskifyRequestException
    {
        $status = $response->status();
        $body = $response->body();
        $json = self::json($response);
        $message = self::message($json, $body, $status);
        $errors = is_array($json['errors'] ?? null) ? $json['errors'] : [];

        return match ($status) {
            401, 403 => new AuthenticationException($message, $status, $body, $errors),
            404 => new NotFoundException($message, $status, $body, $errors),
            422 => new ValidationException($message, $status, $body, $errors),
            default => new ServiceDeskifyRequestException($message, $status, $body, $errors),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function json(Response $response): array
    {
        try {
            $json = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($json) ? $json : [];
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private static function message(array $json, string $body, int $status): string
    {
        foreach (['message', 'error_description', 'error'] as $key) {
            if (is_string($json[$key] ?? null) && $json[$key] !== '') {
                return $json[$key];
            }
        }

        $body = trim($body);

        return $body !== ''
            ? mb_substr($body, 0, 500)
            : "ServiceDeskify returned HTTP {$status}.";
    }
}
