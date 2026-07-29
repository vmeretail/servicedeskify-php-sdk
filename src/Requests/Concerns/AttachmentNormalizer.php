<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Requests\Concerns;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Saloon\Data\MultipartValue;
use SplFileInfo;
use Throwable;

final class AttachmentNormalizer
{
    /**
     * @param  array<int, UploadedFile|SplFileInfo|string>  $attachments
     * @return array<int, MultipartValue>
     */
    public static function multipartValues(array $attachments): array
    {
        $streams = [];

        try {
            $values = [];

            foreach ($attachments as $attachment) {
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

                $streams[] = $stream;
                $filename = $attachment instanceof UploadedFile
                    ? $attachment->getClientOriginalName()
                    : basename($path);

                $values[] = new MultipartValue('attachments[]', $stream, $filename);
            }

            return $values;
        } catch (Throwable $exception) {
            foreach ($streams as $stream) {
                fclose($stream);
            }

            throw $exception;
        }
    }
}
