<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Processor;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ExifStripperProcessor implements FileProcessorInterface
{
    public function process(UploadedFile $file): void
    {
        $mimeType = $file->getMimeType();
        if (null === $mimeType || !\in_array($mimeType, ['image/jpeg', 'image/webp'], true)) {
            return;
        }

        $path = $file->getPathname();

        // Use GD to re-save the image without EXIF.
        // For JPEG
        if ('image/jpeg' === $mimeType) {
            $image = @imagecreatefromjpeg($path);
            if ($image) {
                imagejpeg($image, $path, 95); // 95 is high quality
                imagedestroy($image);
            }
        }
        // For WebP
        elseif ('image/webp' === $mimeType) {
            $image = @imagecreatefromwebp($path);
            if ($image) {
                imagewebp($image, $path);
                imagedestroy($image);
            }
        }
    }
}
