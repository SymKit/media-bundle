<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Processor;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface FileProcessorInterface
{
    /**
     * Processes the file (e.g., modifies content, strips metadata).
     */
    public function process(UploadedFile $file): void;
}
