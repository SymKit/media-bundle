<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Strategy;

use Symkit\MediaBundle\Entity\Media;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface AltTextStrategyInterface
{
    /**
     * Generates alternative text for the given media and uploaded file.
     */
    public function generateAltText(Media $media, UploadedFile $file): ?string;
}
