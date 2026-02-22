<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Security\Rule;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\MimeTypes;
use Symkit\MediaBundle\Security\SecurityException;

final readonly class MimeTypeConsistencyRule implements SecurityRuleInterface
{
    public function check(UploadedFile $file): void
    {
        $mimeType = $file->getMimeType();
        $extension = mb_strtolower($file->getClientOriginalExtension());

        if (null === $mimeType) {
            return;
        }

        $mimeTypes = new MimeTypes();
        $expectedExtensions = $mimeTypes->getExtensions($mimeType);

        // If the extension is not in the list of valid extensions for this MIME type, reject.
        // Note: some types might have many extensions, we check if the current one is among them.
        if (!empty($expectedExtensions) && !\in_array($extension, $expectedExtensions, true)) {
            throw new SecurityException(\sprintf('MIME type mismatch: extension .%s is not valid for MIME type %s.', $extension, $mimeType));
        }
    }
}
