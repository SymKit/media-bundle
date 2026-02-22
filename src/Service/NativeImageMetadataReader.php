<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class NativeImageMetadataReader implements ImageMetadataReaderInterface
{
    public function readMetadata(UploadedFile $file): array
    {
        $dimensions = @getimagesize($file->getPathname());

        if (false === $dimensions) {
            return ['width' => null, 'height' => null];
        }

        return [
            'width' => $dimensions[0],
            'height' => $dimensions[1],
        ];
    }
}
