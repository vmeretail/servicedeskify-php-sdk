<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Requests\Concerns;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Saloon\Data\MultipartValue;
use SplFileInfo;

final class AttachmentNormalizer
{
    /**
     * @param  array<int, UploadedFile|SplFileInfo|string>  $attachments
     * @return array<int, MultipartValue>
     */
    public static function multipartValues(array $attachments): array
    {
        return array_map(static function (UploadedFile|SplFileInfo|string $attachment): MultipartValue {
            $path = match (true) {
                $attachment instanceof UploadedFile => $attachment->getRealPath(),
                $attachment instanceof SplFileInfo => $attachment->getPathname(),
                default => $attachment,
            };

            if (! is_string($path) || ! is_readable($path)) {
                throw new InvalidArgumentException('A ServiceDeskify attachment is not readable.');
            }

            $stream = fopen($path, 'rb');

            if ($stream === false) {
                throw new InvalidArgumentException("Unable to open ServiceDeskify attachment [{$path}].");
            }

            $filename = $attachment instanceof UploadedFile
                ? $attachment->getClientOriginalName()
                : basename($path);

            return new MultipartValue('attachments[]', $stream, $filename);
        }, $attachments);
    }
}
