<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

final readonly class AttachmentDownload
{
    public function __construct(
        public string $fileName,
        public string $contentType,
        public StreamInterface $stream,
    ) {}

    public function saveTo(string $path): void
    {
        $destination = fopen($path, 'wb');

        if ($destination === false) {
            throw new RuntimeException("Unable to open [{$path}] for writing.");
        }

        try {
            if ($this->stream->isSeekable()) {
                $this->stream->rewind();
            }

            while (! $this->stream->eof()) {
                $chunk = $this->stream->read(8192);

                $offset = 0;
                $length = strlen($chunk);

                while ($offset < $length) {
                    $written = fwrite($destination, substr($chunk, $offset));

                    if ($written === false || $written === 0) {
                        throw new RuntimeException("Unable to write attachment to [{$path}].");
                    }

                    $offset += $written;
                }
            }
        } finally {
            fclose($destination);
        }
    }
}
