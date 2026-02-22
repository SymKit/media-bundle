<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Storage;

use RuntimeException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

final class LocalStorage implements StorageInterface
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly string $targetDirectory,
    ) {
    }

    public function upload(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($this->targetDirectory, $fileName);
        } catch (FileException $e) {
            throw new RuntimeException('Failed to upload file: ' . $e->getMessage());
        }

        return $fileName;
    }

    public function delete(string $path): void
    {
        $filePath = $this->targetDirectory . \DIRECTORY_SEPARATOR . $path;

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
