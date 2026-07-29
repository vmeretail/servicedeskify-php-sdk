<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

final readonly class AttachmentData
{
    public function __construct(
        public string $fileName,
        public string $downloadUrl,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            fileName: (string) ($data['file_name'] ?? ''),
            downloadUrl: (string) ($data['download_url'] ?? ''),
        );
    }
}
