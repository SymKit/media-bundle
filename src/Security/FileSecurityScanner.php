<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Security;

use Symkit\MediaBundle\Security\Rule\SecurityRuleInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class FileSecurityScanner implements FileSecurityScannerInterface
{
    /**
     * @param iterable<SecurityRuleInterface> $rules
     */
    public function __construct(
        private readonly iterable $rules,
    ) {
    }

    public function scan(UploadedFile $file): void
    {
        foreach ($this->rules as $rule) {
            $rule->check($file);
        }
    }
}
