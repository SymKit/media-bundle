<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Service;

use Symkit\MediaBundle\Entity\Media;

final readonly class MediaUrlGenerator
{
    public function __construct(
        private readonly string $publicDir,
        private readonly string $mediaPrefix,
    ) {
    }

    public function generateUrl(?Media $media): ?string
    {
        if (null === $media || !$media->getFilename()) {
            return null;
        }

        return $this->mediaPrefix.$media->getFilename();
    }

    public function getAbsolutePath(?Media $media): ?string
    {
        if (null === $media || !$media->getFilename()) {
            return null;
        }

        return $this->publicDir.$this->mediaPrefix.$media->getFilename();
    }
}
