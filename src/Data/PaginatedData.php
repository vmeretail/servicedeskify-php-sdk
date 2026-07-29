<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @template TValue
 *
 * @implements IteratorAggregate<int, TValue>
 */
final readonly class PaginatedData implements Countable, IteratorAggregate
{
    /**
     * @param  array<int, TValue>  $items
     */
    public function __construct(
        public array $items,
        public int $currentPage,
        public int $perPage,
        public int $lastPage,
        public int $total,
        public ?int $from,
        public ?int $to,
        public ?string $firstPageUrl,
        public ?string $lastPageUrl,
        public ?string $nextPageUrl,
        public ?string $previousPageUrl,
    ) {}

    /**
     * @return Traversable<int, TValue>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @template TMapped
     *
     * @param  array<string, mixed>  $payload
     * @param  callable(array<string, mixed>): TMapped  $mapper
     * @return self<TMapped>
     */
    public static function fromArray(array $payload, callable $mapper): self
    {
        $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : $payload;
        $links = is_array($payload['links'] ?? null) ? $payload['links'] : $payload;
        $rows = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        $items = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $items[] = $mapper($row);
            }
        }

        return new self(
            items: $items,
            currentPage: (int) ($meta['current_page'] ?? 1),
            perPage: (int) ($meta['per_page'] ?? count($items)),
            lastPage: (int) ($meta['last_page'] ?? 1),
            total: (int) ($meta['total'] ?? count($items)),
            from: DataMapper::integer($meta['from'] ?? null),
            to: DataMapper::integer($meta['to'] ?? null),
            firstPageUrl: DataMapper::string($links['first'] ?? $links['first_page_url'] ?? null),
            lastPageUrl: DataMapper::string($links['last'] ?? $links['last_page_url'] ?? null),
            nextPageUrl: DataMapper::string($links['next'] ?? $links['next_page_url'] ?? null),
            previousPageUrl: DataMapper::string($links['prev'] ?? $links['prev_page_url'] ?? null),
        );
    }
}
