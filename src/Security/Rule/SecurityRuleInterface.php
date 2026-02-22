<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Security\Rule;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface SecurityRuleInterface
{
    /**
     * Checks the file for security threats.
     *
     * @throws \Symkit\MediaBundle\Security\SecurityException If a threat is detected
     */
    public function check(UploadedFile $file): void;
}
