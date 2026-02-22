<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Security\Rule;

use Symkit\MediaBundle\Security\SecurityException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ImageSpoofingRule implements SecurityRuleInterface
{
    public function check(UploadedFile $file): void
    {
        $mimeType = $file->getMimeType();
        if (null === $mimeType || !str_starts_with($mimeType, 'image/') || 'image/svg+xml' === $mimeType) {
            return;
        }

        // Try to create an image resource from the file content.
        // If it fails, it's likely not a valid image or a corrupted/spoofed one.
        // We use @ to suppress warnings as we handle the failure.
        $image = @imagecreatefromstring(file_get_contents($file->getPathname()));

        if (false === $image) {
            throw new SecurityException('The file claims to be an image but is not a valid raster format or is corrupted.');
        }

        imagedestroy($image);
    }
}
