<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Security\Rule;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symkit\MediaBundle\Security\SecurityException;

final class ArchiveBombRule implements SecurityRuleInterface
{
    private const MAX_COMPRESSION_RATIO = 100; // 1:100 max

    public function check(UploadedFile $file): void
    {
        $extension = mb_strtolower($file->getClientOriginalExtension());
        if (!\in_array($extension, ['tar', 'zip'], true)) {
            return;
        }

        $compressedSize = $file->getSize();
        if ($compressedSize <= 0) {
            return;
        }

        try {
            $archive = new \PharData($file->getPathname());
            $uncompressedSize = 0;

            foreach ($archive as $fileInArchive) {
                /** @var \PharFileInfo $fileInArchive */
                $uncompressedSize += $fileInArchive->getSize();
            }

            $ratio = $uncompressedSize / $compressedSize;

            if ($ratio > self::MAX_COMPRESSION_RATIO) {
                throw new SecurityException(\sprintf('High compression ratio detected (%.2f). Potential decompression bomb.', $ratio));
            }
        } catch (\Exception $e) {
            // Handled by other rules or logged elsewhere
        }
    }
}
