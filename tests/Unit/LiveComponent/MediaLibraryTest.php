<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Tests\Unit\LiveComponent;

use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\TestCase;
use Symkit\MediaBundle\Entity\Media;
use Symkit\MediaBundle\LiveComponent\MediaLibrary;
use Symkit\MediaBundle\Repository\MediaRepositoryInterface;
use Symkit\MediaBundle\Security\FileSecurityScannerInterface;
use Symkit\MediaBundle\Service\ImageMetadataReaderInterface;
use Symkit\MediaBundle\Service\MediaManager;
use Symkit\MediaBundle\Service\MediaUrlGenerator;
use Symkit\MediaBundle\Storage\StorageInterface;
use Symkit\MediaBundle\Strategy\AltTextStrategyInterface;

final class MediaLibraryTest extends TestCase
{
    private function createMediaManager(): MediaManager
    {
        return new MediaManager(
            $this->createMock(StorageInterface::class),
            $this->createMock(AltTextStrategyInterface::class),
            $this->createMock(ImageMetadataReaderInterface::class),
            $this->createMock(FileSecurityScannerInterface::class),
            [],
        );
    }

    public function testGetMediasCallsRepositorySearch(): void
    {
        $paginator = $this->createMock(Paginator::class);
        $repo = $this->createMock(MediaRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('search')
            ->with('test', 1, 24)
            ->willReturn($paginator);

        $library = new MediaLibrary($repo, $this->createMediaManager(), new MediaUrlGenerator('', ''));
        $library->query = 'test';
        $library->page = 1;
        $library->limit = 24;

        self::assertSame($paginator, $library->getMedias());
    }

    public function testOnFilterUpdatedSetsQueryAndResetsPage(): void
    {
        $library = new MediaLibrary(
            $this->createMock(MediaRepositoryInterface::class),
            $this->createMediaManager(),
            new MediaUrlGenerator('', ''),
        );
        $library->page = 3;
        $library->onFilterUpdated('new');
        self::assertSame('new', $library->query);
        self::assertSame(1, $library->page);
    }

    public function testSelectMediaSetsSelectedMediaIdAndFindsMedia(): void
    {
        $media = new Media();
        $repo = $this->createMock(MediaRepositoryInterface::class);
        $repo->method('find')->with(42)->willReturn($media);

        $responder = new \Symfony\UX\LiveComponent\LiveResponder();
        $library = new MediaLibrary($repo, $this->createMediaManager(), new MediaUrlGenerator('/pub', '/media/'));
        $ref = new \ReflectionProperty($library, 'liveResponder');
        $ref->setValue($library, $responder);

        $library->selectMedia(42);
        self::assertSame(42, $library->selectedMediaId);
    }

    public function testSelectMediaWithInvalidIdDoesNotSetSelectedMediaId(): void
    {
        $repo = $this->createMock(MediaRepositoryInterface::class);
        $repo->method('find')->with(999)->willReturn(null);

        $library = new MediaLibrary($repo, $this->createMediaManager(), new MediaUrlGenerator('', ''));
        $library->selectMedia(999);
        self::assertNull($library->selectedMediaId);
    }
}
