<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Strategy;

use Symkit\MediaBundle\Entity\Media;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class FilenameAltTextStrategy implements AltTextStrategyInterface
{
    public function generateAltText(Media $media, UploadedFile $file): ?string
    {
        return pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
    }
}
