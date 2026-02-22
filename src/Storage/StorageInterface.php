<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Storage;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface StorageInterface
{
    /**
     * Uploads a file and returns the stored filename/path.
     */
    public function upload(UploadedFile $file): string;

    /**
     * Deletes a file from storage.
     */
    public function delete(string $path): void;
}
