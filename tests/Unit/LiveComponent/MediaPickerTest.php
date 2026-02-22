<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Tests\Unit\LiveComponent;

use PHPUnit\Framework\TestCase;
use Symkit\MediaBundle\Entity\Media;
use Symkit\MediaBundle\LiveComponent\MediaPicker;
use Symkit\MediaBundle\Repository\MediaRepositoryInterface;

final class MediaPickerTest extends TestCase
{
    public function testGetMediaReturnsNullWhenNoMediaId(): void
    {
        $picker = new MediaPicker($this->createMock(MediaRepositoryInterface::class));
        self::assertNull($picker->getMedia());
    }

    public function testGetMediaReturnsMediaFromRepository(): void
    {
        $media = new Media();
        $repo = $this->createMock(MediaRepositoryInterface::class);
        $repo->method('find')->with(1)->willReturn($media);

        $picker = new MediaPicker($repo);
        $picker->mediaId = 1;
        self::assertSame($media, $picker->getMedia());
    }

    public function testOpenModalSetsModalOpenTrue(): void
    {
        $picker = new MediaPicker($this->createMock(MediaRepositoryInterface::class));
        $picker->openModal();
        self::assertTrue($picker->modalOpen);
    }

    public function testCloseModalSetsModalOpenFalse(): void
    {
        $picker = new MediaPicker($this->createMock(MediaRepositoryInterface::class));
        $picker->modalOpen = true;
        $picker->closeModal();
        self::assertFalse($picker->modalOpen);
    }

    public function testRemoveMediaSetsMediaIdNull(): void
    {
        $picker = new MediaPicker($this->createMock(MediaRepositoryInterface::class));
        $picker->mediaId = 5;
        $picker->removeMedia();
        self::assertNull($picker->mediaId);
    }

    public function testOnMediaSelectedUpdatesMediaIdWhenContextMatches(): void
    {
        $picker = new MediaPicker($this->createMock(MediaRepositoryInterface::class));
        $picker->inputName = 'cover';
        $picker->onMediaSelected(10, 'cover');
        self::assertSame(10, $picker->mediaId);
        self::assertFalse($picker->modalOpen);
    }

    public function testOnMediaSelectedIgnoresWhenContextDoesNotMatch(): void
    {
        $picker = new MediaPicker($this->createMock(MediaRepositoryInterface::class));
        $picker->inputName = 'cover';
        $picker->mediaId = 1;
        $picker->onMediaSelected(10, 'other');
        self::assertSame(1, $picker->mediaId);
    }
}
