<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symkit\MediaBundle\Entity\Media;
use Symkit\MediaBundle\Processor\FileProcessorInterface;
use Symkit\MediaBundle\Security\FileSecurityScannerInterface;
use Symkit\MediaBundle\Storage\StorageInterface;
use Symkit\MediaBundle\Strategy\AltTextStrategyInterface;

final readonly class MediaManager
{
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly AltTextStrategyInterface $altTextStrategy,
        private readonly ImageMetadataReaderInterface $metadataReader,
        private readonly FileSecurityScannerInterface $securityScanner,
        /** @var iterable<FileProcessorInterface> */
        private readonly iterable $processors,
    ) {
    }

    public function createFromUpload(UploadedFile $file): Media
    {
        $media = new Media();
        $this->handleUpload($media, $file);

        return $media;
    }

    public function handleUpload(Media $media, UploadedFile $file): void
    {
        $this->securityScanner->scan($file);

        foreach ($this->processors as $processor) {
            $processor->process($file);
        }

        if ($media->getFilename()) {
            $this->storage->delete($media->getFilename());
        }

        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType() ?? 'application/octet-stream';
        $size = $file->getSize();

        $fileName = $this->storage->upload($file);

        $media->setFilename($fileName);
        $media->setOriginalFilename($originalName);
        $media->setMimeType($mimeType);
        $media->setSize($size);

        if (str_starts_with($mimeType, 'image/')) {
            $dimensions = $this->metadataReader->readMetadata($file);
            $media->setWidth($dimensions['width']);
            $media->setHeight($dimensions['height']);
        }

        if (!$media->getAltText()) {
            $media->setAltText($this->altTextStrategy->generateAltText($media, $file));
        }
    }

    public function deleteMediaFiles(Media $media): void
    {
        if ($media->getFilename()) {
            $this->storage->delete($media->getFilename());
        }
    }
}
