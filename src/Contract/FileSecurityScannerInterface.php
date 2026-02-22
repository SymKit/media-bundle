<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Contract;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface FileSecurityScannerInterface
{
    /**
     * Scans the file for security threats.
     *
     * @throws \Symkit\MediaBundle\Security\SecurityException If a threat is detected
     */
    public function scan(UploadedFile $file): void;
}
