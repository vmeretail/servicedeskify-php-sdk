<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Exceptions;

use RuntimeException;
use Throwable;

class ServiceDeskifyRequestException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $errors
     */
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly ?string $responseBody = null,
        public readonly array $errors = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status ?? 0, $previous);
    }

    public static function transport(Throwable $exception): self
    {
        return new self(
            message: 'The ServiceDeskify request could not be completed.',
            previous: $exception,
        );
    }
}
