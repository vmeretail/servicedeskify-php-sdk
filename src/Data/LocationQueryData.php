<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

final readonly class LocationQueryData
{
    public function __construct(
        public int $page = 1,
        public ?string $store = null,
        public ?string $name = null,
        public ?string $customer = null,
    ) {}

    /**
     * @return array<string, int|string>
     */
    public function query(): array
    {
        return array_filter([
            'page' => $this->page,
            'store' => $this->store,
            'name' => $this->name,
            'customer' => $this->customer,
        ], static fn (mixed $value): bool => $value !== null);
    }

    public function withPage(int $page): self
    {
        return new self(
            page: $page,
            store: $this->store,
            name: $this->name,
            customer: $this->customer,
        );
    }
}
