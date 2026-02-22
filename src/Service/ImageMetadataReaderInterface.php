<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface ImageMetadataReaderInterface
{
    /**
     * Reads metadata (width, height) from the given file.
     *
     * @return array{width: int|null, height: int|null}
     */
    public function readMetadata(UploadedFile $file): array;
}
