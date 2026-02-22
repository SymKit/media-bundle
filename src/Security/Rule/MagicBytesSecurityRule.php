<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Security\Rule;

use Symkit\MediaBundle\Security\SecurityException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class MagicBytesSecurityRule implements SecurityRuleInterface
{
    public function check(UploadedFile $file): void
    {
        $filePath = $file->getPathname();
        $handle = @fopen($filePath, 'r');
        if (!$handle) {
            return;
        }

        $bytes = fread($handle, 4);
        fclose($handle);

        $magicBytes = bin2hex($bytes);

        // ELF (Linux executable)
        if (str_starts_with($magicBytes, '7f454c46')) {
            throw new SecurityException('Linux executable detected.');
        }

        // MZ (Windows executable)
        if (str_starts_with($magicBytes, '4d5a')) {
            throw new SecurityException('Windows executable detected.');
        }

        // Mach-O (MacOS executable) - Big Endian & Little Endian
        if (\in_array($magicBytes, ['feedface', 'feedfacf', 'cefaedfe', 'cffaedfe'], true)) {
            throw new SecurityException('MacOS executable detected.');
        }
    }
}
