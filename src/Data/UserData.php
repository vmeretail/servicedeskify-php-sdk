<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

final readonly class UserData
{
    public function __construct(
        public ?string $username,
        public ?string $email,
        public ?string $avatar,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            username: DataMapper::string($data['username'] ?? null),
            email: DataMapper::string($data['email'] ?? null),
            avatar: DataMapper::string($data['avatar'] ?? null),
        );
    }
}
