<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symkit\MediaBundle\Entity\Media;
use Symkit\MediaBundle\Processor\FileProcessorInterface;
use Symkit\MediaBundle\Security\FileSecurityScannerInterface;
use Symkit\MediaBundle\Service\ImageMetadataReaderInterface;
use Symkit\MediaBundle\Service\MediaManager;
use Symkit\MediaBundle\Storage\StorageInterface;
use Symkit\MediaBundle\Strategy\AltTextStrategyInterface;

final class MediaManagerTest extends TestCase
{
    public function testHandleUploadCallsSecurityScannerThenStorageUpload(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::once())->method('upload')->willReturn('stored-name.jpg');

        $scanner = $this->createMock(FileSecurityScannerInterface::class);
        $scanner->expects(self::once())->method('scan');

        $strategy = $this->createMock(AltTextStrategyInterface::class);
        $strategy->method('generateAltText')->willReturn('Alt text');

        $metadataReader = $this->createMock(ImageMetadataReaderInterface::class);

        $manager = new MediaManager($storage, $strategy, $metadataReader, $scanner, []);

        $path = tempnam(sys_get_temp_dir(), 'symkit_media_');
        self::assertNotFalse($path);
        $file = new UploadedFile($path, 'original.jpg', 'image/jpeg', \UPLOAD_ERR_OK, true);

        $media = new Media();
        $manager->handleUpload($media, $file);

        self::assertSame('stored-name.jpg', $media->getFilename());
        self::assertSame('original.jpg', $media->getOriginalFilename());
        self::assertNotNull($media->getMimeType()); // guessed from file content (temp file may be empty)
        self::assertSame('Alt text', $media->getAltText());
    }

    public function testHandleUploadDeletesPreviousFilenameWhenMediaHasExistingFile(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('upload')->willReturn('new-name.jpg');
        $storage->expects(self::once())->method('delete')->with('old-name.jpg');

        $scanner = $this->createMock(FileSecurityScannerInterface::class);
        $strategy = $this->createMock(AltTextStrategyInterface::class);
        $strategy->method('generateAltText')->willReturn('Alt');
        $metadataReader = $this->createMock(ImageMetadataReaderInterface::class);

        $manager = new MediaManager($storage, $strategy, $metadataReader, $scanner, []);

        $path = tempnam(sys_get_temp_dir(), 'symkit_media_');
        self::assertNotFalse($path);
        $file = new UploadedFile($path, 'new.jpg', 'image/jpeg', \UPLOAD_ERR_OK, true);

        $media = new Media();
        $media->setFilename('old-name.jpg');
        $manager->handleUpload($media, $file);

        self::assertSame('new-name.jpg', $media->getFilename());
    }

    public function testHandleUploadCallsProcessors(): void
    {
        $processor = $this->createMock(FileProcessorInterface::class);
        $processor->expects(self::once())->method('process');

        $storage = $this->createMock(StorageInterface::class);
        $storage->method('upload')->willReturn('out.jpg');
        $scanner = $this->createMock(FileSecurityScannerInterface::class);
        $strategy = $this->createMock(AltTextStrategyInterface::class);
        $strategy->method('generateAltText')->willReturn('Alt');
        $metadataReader = $this->createMock(ImageMetadataReaderInterface::class);

        $manager = new MediaManager($storage, $strategy, $metadataReader, $scanner, [$processor]);

        $path = tempnam(sys_get_temp_dir(), 'symkit_media_');
        self::assertNotFalse($path);
        $file = new UploadedFile($path, 'in.jpg', 'image/jpeg', \UPLOAD_ERR_OK, true);
        $media = new Media();
        $manager->handleUpload($media, $file);
    }

    public function testDeleteMediaFilesCallsStorageDeleteWhenMediaHasFilename(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::once())->method('delete')->with('file.jpg');

        $manager = new MediaManager(
            $storage,
            $this->createMock(AltTextStrategyInterface::class),
            $this->createMock(ImageMetadataReaderInterface::class),
            $this->createMock(FileSecurityScannerInterface::class),
            [],
        );

        $media = new Media();
        $media->setFilename('file.jpg');
        $manager->deleteMediaFiles($media);
    }

    public function testDeleteMediaFilesDoesNothingWhenMediaHasNoFilename(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::never())->method('delete');

        $manager = new MediaManager(
            $storage,
            $this->createMock(AltTextStrategyInterface::class),
            $this->createMock(ImageMetadataReaderInterface::class),
            $this->createMock(FileSecurityScannerInterface::class),
            [],
        );

        $media = new Media();
        $manager->deleteMediaFiles($media);
    }
}
