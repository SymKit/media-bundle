<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Security\Rule;

use Symkit\MediaBundle\Security\SecurityException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class FilenameSecurityRule implements SecurityRuleInterface
{
    private const ILLEGAL_CHARS = "\0\\/*$?\n\t\r\"';";

    public function check(UploadedFile $file): void
    {
        $filename = $file->getClientOriginalName();

        // Forbidden characters
        if (false !== strpbrk($filename, self::ILLEGAL_CHARS)) {
            throw new SecurityException('Filename contains illegal characters.');
        }

        // Directory traversal attempt
        if (str_contains($filename, '..')) {
            throw new SecurityException('Filename contains directory traversal sequence.');
        }

        // Forbidden PHP extensions
        if (preg_match('/\.php[34578]?$/i', $filename) || preg_match('/\.phtml$/i', $filename)) {
            throw new SecurityException('PHP files are strictly forbidden.');
        }
    }
}
