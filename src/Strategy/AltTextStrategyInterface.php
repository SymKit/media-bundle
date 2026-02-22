<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Strategy;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symkit\MediaBundle\Entity\Media;

interface AltTextStrategyInterface
{
    /**
     * Generates alternative text for the given media and uploaded file.
     */
    public function generateAltText(Media $media, UploadedFile $file): ?string;
}
