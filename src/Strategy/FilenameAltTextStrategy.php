<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Strategy;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symkit\MediaBundle\Entity\Media;

final class FilenameAltTextStrategy implements AltTextStrategyInterface
{
    public function generateAltText(Media $media, UploadedFile $file): ?string
    {
        return pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
    }
}
