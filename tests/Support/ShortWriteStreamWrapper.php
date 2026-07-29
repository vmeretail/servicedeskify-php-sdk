<?php

declare(strict_types=1);

namespace Tests\Support;

final class ShortWriteStreamWrapper
{
    public mixed $context;

    public static int $maximumWriteSize = 2;

    /**
     * @var array<string, string>
     */
    private static array $contents = [];

    private string $path = '';

    public static function reset(int $maximumWriteSize = 2): void
    {
        self::$maximumWriteSize = $maximumWriteSize;
        self::$contents = [];
    }

    public static function contents(string $path): string
    {
        return self::$contents[$path] ?? '';
    }

    public function stream_open(
        string $path,
        string $mode,
        int $options,
        ?string &$openedPath,
    ): bool {
        $this->path = $path;
        self::$contents[$path] = '';

        return true;
    }

    public function stream_write(string $data): int
    {
        $written = min(self::$maximumWriteSize, strlen($data));
        self::$contents[$this->path] .= substr($data, 0, $written);

        return $written;
    }

    /**
     * @return array<string, int>
     */
    public function stream_stat(): array
    {
        return [];
    }
}
