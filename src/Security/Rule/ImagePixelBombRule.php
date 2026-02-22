<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Security\Rule;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symkit\MediaBundle\Security\SecurityException;

final class ImagePixelBombRule implements SecurityRuleInterface
{
    private const MAX_PIXELS = 100_000_000; // 100 Megapixels

    public function check(UploadedFile $file): void
    {
        $mimeType = $file->getMimeType();
        if (null === $mimeType || !str_starts_with($mimeType, 'image/')) {
            return;
        }

        // SVG doesn't have fixed pixels in the same way, skipped here.
        if ('image/svg+xml' === $mimeType) {
            return;
        }

        $size = @getimagesize($file->getPathname());
        if (false === $size) {
            return; // Not a raster image or unreadable
        }

        $pixels = $size[0] * $size[1];
        if ($pixels > self::MAX_PIXELS) {
            throw new SecurityException(\sprintf('Image pixel count exceeds threshold (%d MP). Potential DoS attempt.', self::MAX_PIXELS / 1_000_000));
        }
    }
}
