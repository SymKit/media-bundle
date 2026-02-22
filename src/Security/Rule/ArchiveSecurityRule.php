<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Security\Rule;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symkit\MediaBundle\Security\SecurityException;

final class ArchiveSecurityRule implements SecurityRuleInterface
{
    private const EXECUTABLE_MASK = (1 << 6) | (1 << 3) | 1; // --x--x--x
    private const MAX_DEPTH = 10;
    private const ILLEGAL_CHARS = "\0\\/*$?\n\t\r\"';";

    public function check(UploadedFile $file): void
    {
        $extension = mb_strtolower($file->getClientOriginalExtension());

        if (!\in_array($extension, ['tar', 'zip'], true)) {
            return;
        }

        try {
            $archive = new \PharData($file->getPathname());
            $this->scanArchive($archive);
        } catch (\Exception $e) {
            if ($e instanceof SecurityException) {
                throw $e;
            }
        }
    }

    private function scanArchive(\PharData $archive): void
    {
        foreach ($archive as $fileInArchive) {
            /** @var \PharFileInfo $fileInArchive */
            $filename = $fileInArchive->getFilename();

            // Check for illegal characters or traversal in archive members
            if (false !== strpbrk($filename, self::ILLEGAL_CHARS) || str_contains($filename, '..')) {
                throw new SecurityException(\sprintf('Dangerous filename detected in archive: %s', $filename));
            }

            // Check for executable bits
            $perms = $fileInArchive->getPerms();
            if (($perms & self::EXECUTABLE_MASK) !== 0) {
                throw new SecurityException(\sprintf('Executable bit detected in archive member: %s', $filename));
            }

            // Depth check (manual recursion for safety)
            $depth = mb_substr_count($fileInArchive->getPathname(), \DIRECTORY_SEPARATOR);
            if ($depth > self::MAX_DEPTH) {
                throw new SecurityException('Archive structure is too deep.');
            }
        }
    }
}
